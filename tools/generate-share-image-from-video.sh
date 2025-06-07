#!/bin/bash

# Check if a filename was provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <input_video_file>"
    echo "Example: $0 my_video.mp4"
    exit 1
fi

# Get the input filename and prepend the screenshots directory
input_file="screenshots/$1"

# Check if the input file exists
if [ ! -f "$input_file" ]; then
    echo "Error: File '$input_file' not found."
    exit 1
fi

# Extract the first frame
echo "Extracting first frame from '$input_file'..."
ffmpeg -i "$input_file" -vframes 1 -vf scale=1200:630 -q:v 2 share-image.png

# Check if ffmpeg succeeded
if [ $? -eq 0 ]; then
    echo "Successfully created share-image.png"
else
    echo "Error: Failed to extract frame from video"
    exit 1
fi