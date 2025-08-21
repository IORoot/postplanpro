# PostPlanPro

This plugin is used to schedule posts / media to social platforms.

## Prerequisites

1. Wordpress
2. ACF Pro (Needs pro features - repeater)
3. [simple-calendar-acf](https://wordpress.org/plugins/simple-calendar-acf/) plugin. (This is for the calendar view) [optional]
4. Make.com Scenario


## Intro

The idea is to programatically generate social media posts. There are many parts
to the pipeline, but in essense the following is what should happen:

1. Video + thumbnail is created on server with public URL.
2. This plugin is notified and a new 'release' is created.
3. The release is assigned a schedule that determines when it should be posted.
4. A template for each social platform is associated with that schedule.
5. The release is updated using the templates for each social profile.
6. The publication date is changed to meet the schedule requirements.
7. When the publication date hits, all data is sent to make.com via a webhook.
8. Make.com has a scenario that will post to each social media platform.


