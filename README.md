# WP Show Tracker #
[![GitHub license](https://img.shields.io/badge/license-GPLv3-blue.svg)](https://raw.githubusercontent.com/jazzsequence/wp-show-tracker/develop/LICENSE.md) ![Travis CI](https://travis-ci.org/jazzsequence/WP-Show-Tracker.svg?branch=develop)

**Contributors:**      Chris Reynolds  
**Donate link:**       http://jazzsequence.com  
**Tags:**  
**Requires at least:** 4.3  
**Tested up to:**      4.3  
**Stable tag:**        0.4.0  
**License:**           GPLv3  
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html  

## Description ##

Track shows that you (or your kids) watch. Set a weekly limit and display an alert when the limit is reached for that viewer.

## Installation ##

### Manual Installation ###

1. Upload the entire `/wp-show-tracker` directory to the `/wp-content/plugins/` directory.
2. Activate WP Show Tracker through the 'Plugins' menu in WordPress.

## Frequently Asked Questions ##


## Screenshots ##
![autosuggest](https://www.evernote.com/shard/s19/sh/40ed7517-6317-4599-bde2-76e20a0c5423/67ab3d66aeabcc7e/res/4500b424-08ba-423d-b11e-0b5e01c90ce4/show-tracker-title.gif)

![shows admin](https://www.evernote.com/l/ABPS4wftsA5BhZru9qqJ_Md-RgwmkPWxrWAB/image.png)

![viewers admin](https://www.evernote.com/l/ABN4INbSJD5Fl4UCKym5kGcJg7UiCKv2CDgB/image.png)

![options page](https://www.evernote.com/l/ABPbIgidTaNA3boDDEEjCbdfM5uEbzPBlLwB/image.png)

## Changelog ##

### 0.4.0
* Integrated TGM Plugin Activation for WP-JSON API recommended plugin.

### 0.3.0 ###
* Added jQuery autocomplete to autosuggest shows based on an array of titles fetched via the WP REST API.
* Added some minor CSS for the autocomplete and the radio list of viewers in the form.

### 0.2.0 ###
* Initial functional beta release
* Added major functionality for tracking shows to users and displaying the front-end submission form.
* Added messaged that display above the form with number of shows watched for each viewer.
* Added js to control hiding viewers who've watched their max alotted shows.

### 0.1.0 ###
* First release
