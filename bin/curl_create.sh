#!/bin/bash

curl -X POST https://localhost:8443/wp-json/custom/v1/release \
-H "Content-Type: application/json" \
-H "X-API-TOKEN: alpha" \
-d '{
    "token": "abcdef",
    "title": "New Release Title",
    "content": "This is the content of the new release.",
    "acf": {
        "ppp_release_method": "true",
        "ppp_release_schedule": "Test Schedule 1 hour",
        "ppp_video_url": "http://media.londonparkour.com/processed/batch_processed/teens_06/processed.mp4",
        "ppp_thumbnail_url": "http://media.londonparkour.com/processed/batch_processed/teens_06/thumbnail.png"
    }
}'
