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

# Get actual video dimensions and SAR
video_info=$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height,sample_aspect_ratio -of csv=p=0 "$input_file")
src_width=$(echo "$video_info" | cut -d',' -f1)
src_height=$(echo "$video_info" | cut -d',' -f2)
sar=$(echo "$video_info" | cut -d',' -f3)

# Calculate display width accounting for SAR (Sample Aspect Ratio)
if [ -n "$sar" ] && [ "$sar" != "N/A" ] && [ "$sar" != "1:1" ]; then
    sar_num=$(echo "$sar" | cut -d':' -f1)
    sar_den=$(echo "$sar" | cut -d':' -f2)
    display_width=$(echo "scale=2; $src_width * $sar_num / $sar_den" | bc)
else
    display_width=$src_width
fi

echo "DEBUG: Storage dimensions: ${src_width}x${src_height}, SAR: $sar"
echo "DEBUG: Display width: $display_width"

# Calculate target height based on display dimensions
target_height=$(echo "scale=0; 1200 * $src_height / $display_width" | bc)
# Make sure height is even
if [ $((target_height % 2)) -ne 0 ]; then
    target_height=$((target_height + 1))
fi

# Extract the first frame
echo "Extracting first frame from '$input_file' (${src_width}x${src_height})..."
echo "Output will be: ${target_height}px tall (1200x${target_height})"
echo "Output will be saved to: $output_path"
ffmpeg -i "$input_file" -vframes 1 -vf "scale=1200:${target_height}:flags=lanczos" -q:v 2 "$output_path"

# Check if ffmpeg succeeded
if [ $? -eq 0 ]; then
    echo "Successfully created $output_path"
else
    echo "Error: Failed to extract frame from video"
    exit 1
fi