# Contributing

## Validating

Run the linter and the unit tests with:

```bash
composer install
composer lint
composer test
```

## Releasing

### Changelog and version number

Describe the changes made compared to the last released version in the
[changelog](../README.md#Changelog). Browse the git history to make sure nothing
has been left out.

Choose the next version number according to the rules of
[Semantic Versioning](https://semver.org/) and set it by creating a new git
tag:

```bash
git commit -am "Releasing 1.2.3"
git push
git tag 1.2.3
git push --tags
```

Packagist will automatically pick up the new tag and create a new release. See
https://packagist.org/about
