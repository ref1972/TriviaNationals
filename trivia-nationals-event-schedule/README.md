# Trivia Nationals Event Schedule

WordPress plugin for managing Trivia Nationals events and rendering them with:

- rich event descriptions using the normal WordPress block/classic editor
- images and captions embedded directly in descriptions
- featured images for event cards
- start/end time, location, and registration/info URL fields
- `[trivia_nationals_event_schedule]` shortcode for the public schedule

## Install

Zip the `trivia-nationals-event-schedule` folder, upload it in WordPress under **Plugins > Add New > Upload Plugin**, and activate it.

After activation, add events under **Events** in the WordPress admin. Use the editor's **Add Media** button or image block to place graphics directly inside the event description.

## Shortcode

Place this shortcode on the schedule page:

```text
[trivia_nationals_event_schedule]
```

Optional attributes:

```text
[trivia_nationals_event_schedule limit="50" show_past="true"]
```
