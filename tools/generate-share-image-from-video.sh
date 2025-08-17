#!/bin/bash

# Check if a filename was provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <input_video_file>"
    echo "Example: $0 my_video.mp4"
    exit 1
fi

# Get the input filename
input_file="$1"

# Check if the input file exists
if [ ! -f "$input_file" ]; then
    echo "Error: File '$input_file' not found."
    exit 1
fi

# Get the directory one level up from the video's location
video_dir=$(dirname "$input_file")
parent_dir=$(dirname "$video_dir")
output_path="$parent_dir/share-image.png"

# Extract the first frame
echo "Extracting first frame from '$input_file'..."
echo "Output will be saved to: $output_path"
ffmpeg -i "$input_file" -vframes 1 -vf scale=1200:630 -q:v 2 "$output_path"

# Check if ffmpeg succeeded
if [ $? -eq 0 ]; then
    echo "Successfully created $output_path"
else
    echo "Error: Failed to extract frame from video"
    exit 1
fi