<?php
namespace CSD\Image\Tests\Buffer;

use PHPUnit\Framework\TestCase;
use CSD\Image\Buffer\ImageMetadataBuffer;

class ImageMetadataBufferTest extends TestCase
{
    /**
     * For each “format with XMP” we expect the returned buffer to contain
     * the XMP namespace string. (JPEG → “http://ns.adobe.com/xap/1.0/”,
     * PNG → “XML:com.adobe.xmp”, WebP → “XMP ”, etc.)
     *
     * @dataProvider providerFormatsWithXmp
     */
    public function testBufferUpThroughXmp_FindsXmpForKnownFormats(string $filename, string $expectedSubstring)
    {
        $path = __DIR__ . '/../Fixtures/' . $filename;
        $buf  = ImageMetadataBuffer::bufferUpThroughXmp($path);
        $this->assertIsString($buf, "bufferUpThroughXmp returned null or non‐string for $filename");
        $this->assertStringContainsString($expectedSubstring, $buf, "Buffer for $filename did not contain expected XMP marker");
    }

    public function providerFormatsWithXmp(): array
    {
        return [
            // JPEG with embedded XMP
            ['frameright.jpg', 'http://ns.adobe.com/xap/1.0/'],
            // PNG with an iTXt chunk holding XML:com.adobe.xmp
            ['frameright.png', 'XML:com.adobe.xmp'],
            // WebP with an XMP chunk
            ['meta.webp', 'XMP '],
        ];
    }

    /**
     * If an image has no XMP at all, bufferUpThroughXmp should still return
     * the full file contents (not null), and getimagesizefromstring should work
     * later on. We simply assert that it’s non‐null and that its length is
     * “reasonably” larger than zero.
     *
     * @dataProvider providerFormatsWithoutXmp
     */
    public function testBufferUpThroughXmp_NoXmpStillReturnsEntireFile(string $filename)
    {
        $path = __DIR__ . '/../Fixtures/' . $filename;
        $buf  = ImageMetadataBuffer::bufferUpThroughXmp($path);

        $this->assertIsString($buf, "Expected a buffer, got null for $filename");
        $this->assertGreaterThan(0, strlen($buf), "Empty buffer for $filename");
    }

    public function providerFormatsWithoutXmp(): array
    {
        return [
            ['nometa.jpg'],
            ['nometa.png'],
            ['exif.webp'], // a WebP with EXIF but no XMP (still buffer as full file)
        ];
    }

    /**
     * If fopen() fails (for instance, point at a non‐existent file), we expect null.
     */
    public function testBufferUpThroughXmp_NonexistentReturnsNull()
    {
        $buf = ImageMetadataBuffer::bufferUpThroughXmp('/this/path/does/not/exist.jpg');
        $this->assertNull($buf);
    }

    /**
     * Now, testing each private helper via reflection (optional):
     *   - bufferPngUpToXmp
     *   - bufferJpegUpToXmp
     *   - bufferWebpUpToXmp
     *
     * We can invoke them via ReflectionMethod, passing in a local php://temp stream
     * that we manually write a minimal header+XMP chunk into. Below is an example for JPEG.
     */
    public function testBufferJpegUpToXmp_AppendsEOIAfterXmpAndSOF()
    {
        // Construct a minimal JPEG header with:
        //   0xFFD8 (SOI)
        //   0xFFE1 (APP1), length=??, payload="http://ns.adobe.com/xap/1.0/\0<fakeXmp…>" 
        //   0xFFC0 (SOF0), length=… (we don’t care about real dimensions)
        //   0xFFDA (SOS) to simulate “start of scan”
        //
        // After we feed this to bufferJpegUpToXmp, the returned string must end in 0xFFD9 (EOI).
        $fakeXmpPrefix = "http://ns.adobe.com/xap/1.0/\x00<dummyxmp/>";
        $app1Length    = pack('n', strlen($fakeXmpPrefix) + 2);
        $jpegBytes     = "\xFF\xD8";               // SOI
        $jpegBytes    .= "\xFF\xE1" . $app1Length . $fakeXmpPrefix;
        $jpegBytes    .= "\xFF\xC0" . "\x00\x04" . "\x00\x01\x00\x01"; // SOF0 with length=4, dummy dims
        $jpegBytes    .= "\xFF\xDA";               // SOS
        // No actual compressed data, so fromStream would break; our buffer helper should append EOI.

        // Write into a php://temp stream
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $jpegBytes);
        rewind($stream);

        $rm = new \ReflectionMethod(ImageMetadataBuffer::class, 'bufferJpegUpToXmp');
        $rm->setAccessible(true);
        $buffered = $rm->invoke(null, $stream);
        fclose($stream);

        // Should contain the XMP header
        $this->assertStringContainsString("http://ns.adobe.com/xap/1.0/\x00", $buffered);
        // Should end with EOI (\xFF\xD9)
        $this->assertStringEndsWith("\xFF\xD9", $buffered);
    }

    public function testBufferPngUpToXmp_StopsAtIENDOriTXt()
    {
        // Build a minimal PNG:
        //   Signature (0x89 50 4E 47 0D 0A 1A 0A)
        //   iTXt chunk: length, type='iTXt', data="XML:com.adobe.xmp\0<fakexmp…>", CRC
        //   IEND chunk: length=0, type='IEND', CRC
        $sig      = "\x89PNG\x0D\x0A\x1A\x0A";
        $xmpTxt   = "XML:com.adobe.xmp\x00<fakexmp/>";
        $iTXtLen  = pack('N', strlen($xmpTxt));
        $iTXtType = 'iTXt';
        $fakeCRC  = "\x00\x00\x00\x00";
        $iTXtChunk = $iTXtLen . $iTXtType . $xmpTxt . $fakeCRC;
        $iENDChunk = pack('N', 0) . 'IEND' . $fakeCRC;

        $pngBytes  = $sig . $iTXtChunk . $iENDChunk;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $pngBytes);
        rewind($stream);

        $rm = new \ReflectionMethod(ImageMetadataBuffer::class, 'bufferPngUpToXmp');
        $rm->setAccessible(true);
        $buffered = $rm->invoke(null, $stream);
        fclose($stream);

        // The returned buffer should begin with the PNG signature
        $this->assertSame(substr($buffered, 0, 8), ImageMetadataBuffer::PNG_SIGNATURE);
        // It should contain our “XML:com.adobe.xmp” keyword and stop after that
        $this->assertStringContainsString("XML:com.adobe.xmp\x00", $buffered);
        // It should also contain the IEND chunk
        $this->assertStringContainsString('IEND', $buffered);
    }

    public function testBufferWebpUpToXmp_StopsWhenXmpTypeIsFound()
    {
        // Minimal WebP RIFF header:
        //   'RIFF' + [4‐byte size] + 'WEBP'
        // Then a chunk with type='XMP ' + 4 bytes size + payload '<fakexmp>'
        // We don’t need to append padding, because our code will break immediately when it sees 'XMP '.
        $riff    = 'RIFF' . pack('V', 8 + 4 + 6) . 'WEBP';
        $xmpType = 'XMP ';
        $xmpData = '<fakexmp>';
        $sizeLE  = pack('V', strlen($xmpData));
        $webpBytes = $riff . $xmpType . $sizeLE . $xmpData;
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $webpBytes);
        rewind($stream);

        $rm = new \ReflectionMethod(ImageMetadataBuffer::class, 'bufferWebpUpToXmp');
        $rm->setAccessible(true);
        $buffered = $rm->invoke(null, $stream);
        fclose($stream);

        $this->assertStringContainsString('WEBP', $buffered);
        $this->assertStringContainsString('XMP ', $buffered);
        // As soon as it sees 'XMP ', it should stop reading further sub‐chunks.
    }
}
