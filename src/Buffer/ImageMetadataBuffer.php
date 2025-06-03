<?php

namespace CSD\Image\Buffer;

class ImageMetadataBuffer
{
    const JPEG_SOI              = "\xFF\xD8"; // Start of Image (SOI) marker
    const JPEG_SOS              = "\xFF\xDA"; // Start of Scan (SOS) marker
    const JPEG_EOI              = "\xFF\xD9"; // End of Image (EOI) marker
    const JPEG_APP1_MARKER      = "\xFF\xE1"; // APP1 segment marker (where EXIF/XMP live)
    const JPEG_APP1_XMP_HEADER  = "http://ns.adobe.com/xap/1.0/\x00";

    const RIFF_SIGNATURE        = 'RIFF'; // first four bytes of any RIFF container
    const WEBP_SIGNATURE        = 'WEBP'; // four bytes after RIFF, in a WebP file
    const PNG_SIGNATURE         = "\x89PNG\x0D\x0A\x1A\x0A"; // first eight bytes of any PNG file
    const PNG_ITXT_XMP_KEYWORD  = "XML:com.adobe.xmp\x00";


    /**
     * Given a URL or local path, buffer just enough bytes (PNG chunks,
     * JPEG segments, or WebP sub‐chunks) to include any XMP metadata.
     * If fopen() fails, fall back to file_get_contents()
     * and return the entire file as a buffer.
     *
     * @param string $url
     * @return string|null
     */
    public static function bufferUpThroughXmp($url)
    {
        $stream = @fopen($url, 'rb');
        if (! $stream) {
            $all = @file_get_contents($url);
            if ($all === false) {
                return null;
            }
            return $all === '' ? null : $all;
        }

        // 2) Peek the first 12 bytes to sniff format
        $peek = fread($stream, 12);
        if ($peek === false || strlen($peek) < 2) {
            fclose($stream);
            return null;
        }

        if (substr($peek, 0, 8) === self::PNG_SIGNATURE) {
            fclose($stream);
            $stream = @fopen($url, 'rb');
            if (! $stream) {
                return null;
            }
            $buf = self::bufferPngUpToXmp($stream);
            fclose($stream);
            return $buf;
        }

        if (substr($peek, 0, 2) === self::JPEG_SOI) {
            fclose($stream);
            $stream = @fopen($url, 'rb');
            if (! $stream) {
                return null;
            }
            $buf = self::bufferJpegUpToXmp($stream);
            fclose($stream);
            return $buf;
        }

        if (
            strlen($peek) >= 12
            && substr($peek, 0, 4) === self::RIFF_SIGNATURE
            && substr($peek, 8, 4) === self::WEBP_SIGNATURE
        ) {
            fclose($stream);
            $stream = @fopen($url, 'rb');
            if (! $stream) {
                return null;
            }
            $buf = self::bufferWebpUpToXmp($stream);
            fclose($stream);
            return $buf;
        }

        // Unknown format: just read entire file
        fclose($stream);
        $stream = @fopen($url, 'rb');
        if (! $stream) {
            return null;
        }
        $all = stream_get_contents($stream);
        if ($all === false) {
            fclose($stream);
            return null;
        }
        fclose($stream);
        return $all === '' ? null : $all;
    }

    /**
     * Buffer a PNG chunk‐by‐chunk until we fully read an iTXt whose
     * data begins with "XML:com.adobe.xmp\x00", and then read through
     * the IEND chunk before stopping.
     *
     * @param resource $stream  Opened PNG stream in binary mode
     * @return string|null      A byte‐buffer containing: signature → iTXt(XMP) → IEND (or null on error)
     */
    private static function bufferPngUpToXmp($stream)
    {
        // 1) Read and verify the 8‐byte PNG signature
        $sig = fread($stream, 8);
        if ($sig === false || strlen($sig) < 8) {
            return null;
        }
        if ($sig !== self::PNG_SIGNATURE) {
            return null;
        }

        $buffer   = $sig;
        $foundXmp = false;

        while (true) {
            // 2) Read the next chunk's length+type (8 bytes)
            $hdr = fread($stream, 8);
            if ($hdr === false || strlen($hdr) < 8) {
                // EOF or truncated; return what we have so far
                break;
            }
            $buffer .= $hdr;

            // Parse length (4 bytes BE) and chunk type (4 bytes ASCII)
            $u = @unpack('Nlength/a4type', $hdr);
            if ($u === false || ! isset($u['length'], $u['type'])) {
                // Invalid header, bail
                break;
            }
            $length = (int) $u['length'];
            $type   = $u['type'];

            // 3) If this is IEND, read its 4‐byte CRC, append, and stop
            if ($type === 'IEND') {
                $crc = fread($stream, 4);
                if ($crc !== false && strlen($crc) === 4) {
                    $buffer .= $crc;
                }
                break;
            }

            // 4) Otherwise, read payload + 4‐byte CRC
            $toRead = $length + 4;
            if ($toRead > 0) {
                $chunkData = fread($stream, $toRead);
                if ($chunkData === false || strlen($chunkData) < $toRead) {
                    // Truncated payload; append whatever we got and bail
                    $buffer .= ($chunkData ?: '');
                    break;
                }
                $buffer .= $chunkData;
            } else {
                $chunkData = '';
            }

            // 5) If this is an iTXt chunk and it begins with the XMP keyword, mark $foundXmp
            if ($type === 'iTXt') {
                $data = substr($chunkData, 0, $length);
                if (strpos($data, self::PNG_ITXT_XMP_KEYWORD) === 0) {
                    $foundXmp = true;
                    // Do NOT break yet – we still need to read through IEND
                }
            }

            // 6) If we've seen XMP‐tagged iTXt, continue looping until we hit IEND above
        }

        return $buffer;
    }

    /**
     * Buffer a JPEG segment‐by‐segment until we have:
     *   1) Fully read the APP1-XMP chunk (so $foundXmp = true),
     *   2) Fully read the first SOF segment (so $sawSOF = true),
     *   3) Then append an EOI marker (0xFFD9) and break.
     *
     * If we encounter SOS (0xFFDA) or EOI (0xFFD9) before capturing both,
     * we break anyway, because no more headers exist.
     *
     * @param resource $stream
     * @return string|null
     */
    private static function bufferJpegUpToXmp($stream)
    {
        // 1) Read SOI (2 bytes). Must be 0xFFD8.
        $soi = fread($stream, 2);
        if ($soi === false || strlen($soi) < 2 || $soi !== self::JPEG_SOI) {
            return null;
        }

        $buffer   = $soi;
        $foundXmp = false;
        $sawSOF   = false;

        while (true) {
            $marker = fread($stream, 2);
            if ($marker === false || strlen($marker) < 2) {
                // EOF or truncated
                break;
            }
            $buffer .= $marker;

            // If SOS (0xFFDA) or EOI (0xFFD9) appear before we've captured both flags,
            // break anyway (no more headers).
            if ($marker === self::JPEG_SOS) {
                if (! ($foundXmp && $sawSOF)) {
                    break;
                }
                // Both flags true, we’ll append EOI and stop.
                break;
            }
            if ($marker === self::JPEG_EOI) {
                break;
            }

            $lenBytes = fread($stream, 2);
            if ($lenBytes === false || strlen($lenBytes) < 2) {
                break;
            }
            $buffer .= $lenBytes;

            $un = @unpack('nsegmentLength', $lenBytes);
            if ($un === false || ! isset($un['segmentLength'])) {
                break;
            }
            $segLen     = (int) $un['segmentLength'];
            $payloadLen = $segLen - 2;

            if ($payloadLen > 0) {
                $payload = fread($stream, $payloadLen);
                if ($payload === false || strlen($payload) < $payloadLen) {
                    $buffer .= ($payload ?: '');
                    break;
                }
                $buffer .= $payload;
            } else {
                $payload = '';
            }

            // If this marker is APP1 (0xFFE1), check for XMP header
            if ($marker === self::JPEG_APP1_MARKER) {
                $xmpHeader = self::JPEG_APP1_XMP_HEADER;
                if (strncmp($payload, $xmpHeader, strlen($xmpHeader)) === 0) {
                    $foundXmp = true;
                }
            }

            // Check if this is a SOF marker (0xFFC0,0xFFC1,0xFFC2,…)
            $secondByte = ord($marker[1]);
            $isSOF = in_array($secondByte, [
                0xC0, 0xC1, 0xC2, 0xC3,
                0xC5, 0xC6, 0xC7,
                0xC9, 0xCA, 0xCB,
                0xCD, 0xCE, 0xCF,
            ], true);
            if ($isSOF) {
                $sawSOF = true;
            }

            // If we now have both APP1-XMP and SOF, stop reading further segments.
            if ($foundXmp && $sawSOF) {
                break;
            }
        }

        // We've captured XMP and SOF (if present).
        // Append EOI (0xFFD9) so that fromStream() won't unpack an empty marker.
        $buffer .= self::JPEG_EOI;

        return $buffer;
    }

    /**
     * Buffer a WebP RIFF sub‐chunk by sub‐chunk until we find "XMP " or "EXIF",
     * then stop. If none, buffer until EOF.
     *
     * @param resource $stream
     * @return string|null
     */
    private static function bufferWebpUpToXmp($stream)
    {
        $riffHdr = fread($stream, 12);
        if ($riffHdr === false || strlen($riffHdr) < 12) {
            return null;
        }
        if (substr($riffHdr, 0, 4) !== self::RIFF_SIGNATURE || substr($riffHdr, 8, 4) !== self::WEBP_SIGNATURE) {
            return null;
        }

        $buffer = $riffHdr;

        while (true) {
            $hdr = fread($stream, 8);
            if ($hdr === false || strlen($hdr) < 8) {
                break;
            }
            $buffer .= $hdr;

            $type   = substr($hdr, 0, 4);
            $sizeLE = substr($hdr, 4, 4);

            $un = @unpack('VchunkSize', $sizeLE);
            if ($un === false || ! isset($un['chunkSize'])) {
                break;
            }
            $chunkSize = (int) $un['chunkSize'];

            if ($chunkSize > 0) {
                $data = fread($stream, $chunkSize);
                if ($data === false || strlen($data) < $chunkSize) {
                    $buffer .= ($data ?: '');
                    break;
                }
                $buffer .= $data;

                if ($chunkSize % 2 !== 0) {
                    $pad = fread($stream, 1);
                    if ($pad !== false && strlen($pad) === 1) {
                        $buffer .= $pad;
                    }
                }
            }

            if ($type === 'XMP ' || $type === 'EXIF') {
                break;
            }
        }

        return $buffer;
    }
}
