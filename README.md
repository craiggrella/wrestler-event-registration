# Wrestler Event Registration

WordPress plugin for managing wrestler event registrations with FluentCRM integration.

## Description

Allows parents to register their wrestlers for events. Parents can mark each wrestler as "Attending" or "Declined". The system tracks all registrations and displays live response counts.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- FluentCRM plugin installed and activated
- Custom post type: `event`

## FluentCRM Custom Fields Required

The plugin expects the following custom fields in FluentCRM (for up to 5 wrestlers per parent):

- `wrestler_1_id`, `wrestler_2_id`, etc.
- `wrestler_1_first_name`, `wrestler_2_first_name`, etc.
- `wrestler_1_last_name`, `wrestler_2_last_name`, etc.

## Installation

1. Upload the `wrestler-event-registration` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Database table will be created automatically on activation

## Usage

Add this shortcode to you
