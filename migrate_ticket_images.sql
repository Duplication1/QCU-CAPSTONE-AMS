-- Migration: Add image support to issues table
-- Date: 2026-02-24
-- Description: Adds image_path column to store uploaded ticket images

ALTER TABLE `issues` 
ADD COLUMN `image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded issue image' 
AFTER `description`;

-- Add an index for image_path lookups (optional but recommended)
-- CREATE INDEX idx_image_path ON issues(image_path);
