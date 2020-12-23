# WordPress Plugin Trouble Detector
[![Build Status](https://travis-ci.com/jenkoian/wp-plugin-trouble-detector.svg?branch=main)](https://travis-ci.com/jenkoian/wp-plugin-trouble-detector)

Installing WordPress plugins via composer? Worried about the trepidations of installing plugins and there being clashes between any dependencies and your own?

> Don't know what I'm on about? This post does a good job of explaining https://inpsyde.com/en/package-management-in-wordpress-introduction-solutions/

## What this will do

This is a composer plugin that will inspect the wordpress plugins you are installing with composer and yell if it spots anything that could mean you're in for a world of pain.

## Installation

`composer req jenko/wp-plugin-trouble-detector --dev`

## Usage

Composer install as usual, if any package has anything to be concerned about, you will see yellow warnings in the output.

## What it yells about

* Plugin has a committed `vendor` directory
* Plugin has third party dependencies which clash with yours (includes support for wpackagist plugins)

## Troubleshooting

It should 'just work' but if for whatever reason it doesn't you may need to add the following to your `scripts` section of `composer.json`

```json
    "scripts": {
        "post-package-install": [
            "Jenko\\WpPluginTroubleDetector\\Plugin::onPostPackageInstall"
        ]
    }
```
