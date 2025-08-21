#!/bin/bash

curl -X PUT https://localhost:8443/wp-json/custom/v1/release/10138 \
-H "Content-Type: application/json" \
-H "X-API-TOKEN: charlie" \
-d '{
    "title": "UPDATED Release Title",
    "content": "This is the UPDATED again of the new release.",
    "acf": {
        "ppp_release_method": "true",
        "ppp_release_schedule": "Test Schedule 1 hour",
        "ppp_video_url": "http://media.londonparkour.com/processed/batch_processed/teens_06/processed.mp4",
        "ppp_thumbnail_url": "http://media.londonparkour.com/processed/batch_processed/teens_06/thumbnail.png"
    }
}'
