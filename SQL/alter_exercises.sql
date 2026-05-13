ALTER TABLE exercise
ADD COLUMN youtube_url VARCHAR(500) NULL;

UPDATE exercise
SET youtube_url = CASE name
  WHEN 'Cycling' THEN 'https://www.youtube.com/watch?v=8LZ5wZgW5lU'
  WHEN 'Yoga' THEN 'https://www.youtube.com/watch?v=v7AYKMP6rOE'
  WHEN 'Pilates' THEN 'https://www.youtube.com/watch?v=7rVha5hXMGQ'
  WHEN 'Push-ups' THEN 'https://www.youtube.com/watch?v=IODxDxX7oi4'
  WHEN 'Squats' THEN 'https://www.youtube.com/watch?v=aclHkVaku9U'
  ELSE youtube_url
END
WHERE youtube_url IS NULL OR youtube_url = '';
