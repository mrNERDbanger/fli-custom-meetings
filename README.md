# FLI Custom Meetings

A WordPress plugin that automatically schedules and manages monthly meetings with Zoom integration, while intelligently handling federal holidays.

## Features

- **Automated Meeting Scheduling**
  - Monthly Topic with Rhonda (First Monday)
  - Get-it-Done! Session (Second Monday)
  - Q&A: Ask Us Anything (Third Monday)
  - Monthly Reflection (Fourth Monday)
  - Custom Meeting (Configurable)

- **Smart Scheduling**
  - Automatically skips federal holidays
  - Reschedules to next available Monday
  - Creates meetings one month in advance

- **Zoom Integration**
  - Automatic Zoom meeting creation
  - Secure meeting links
  - Join button appears 15 minutes before meeting

- **Display Features**
  - Countdown timer for upcoming meetings
  - "Meeting is Live!" status
  - Meeting details (date, time, description)
  - Responsive design

## Installation

1. Download and extract the plugin to `/wp-content/plugins/fli-custom-meetings`
2. Activate the plugin through the WordPress admin panel
3. Go to Settings > Custom Meetings
4. Enter your Zoom API credentials
5. Configure custom meeting settings if needed

## Usage

Use the shortcode to display meetings: 

## Changelog

### 1.5.1
- Fixed initialization order for logging system
- Improved plugin activation process
- Added error handling for logger initialization

### 1.5.0
- Initial release
- Added automated meeting scheduling
- Integrated Zoom API
- Added federal holiday handling
- Implemented countdown timer
- Added logging system 