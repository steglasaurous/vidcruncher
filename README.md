# Vid Cruncher!

A system for receiving video files, splitting them into chunks (if necessary), and re-encoding them into AV1 across
multiple encoding machines.  

# Setup

## Requirements

- All roles require a running docker desktop installation.  Tested with docker desktop on windows, linux and macos.  

## Coordinator

This run an API and database to coordinate actions.  To get running:

1. Create `.env.local` and populate the following values:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@db:5432/app?serverVersion=14&charset=utf8"

# URL to the coordinator - used for downloading and uploading video data and generating URLs
COORDINATOR_BASE_URL=http://db:8000
```

2. `docker compose build`
3. `docker compose up web db cron` - Starts up coordinator containers.  Technically you can start an encoder on the same host as well if it makes sense.

## Encoder

For each worker machine, do the following.

1. Create `.env.local` and populate the following values:

```dotenv
# Populate with the IP of the machine running the coordinator - The database is used for symfony messenger to pick up
# jobs, but otherwise doesn't use it. (The API is used for all other communications)
DATABASE_URL="postgresql://app:!ChangeMe!@10.0.0.79:5432/app?serverVersion=14&charset=utf8"

# URL to the coordinator - used for downloading and uploading video data
COORDINATOR_BASE_URL=http://10.0.0.79:8000
```

2. `docker compose build encoder`
3. `docker compose up -d encoder` - Make sure to specify encoder, so only the encoder runs on this worker. 

# Usage

By default, any files dropped into public/videos/recordings will get picked up for processing.  

For live-recordings, set files to be dropped into public/videos/live-recordings.  They will be picked up for processing
2 minutes after their last modified time to avoid picking up a file too soon.  The fully-assembled result will be created
10 minutes after the last file's modified time, to ensure all files are captured.  

After files are processed, their fully-assembled results are placed in public/videos/done

# Stopping encoders

To gracefully stop encoder containers, use the following command on each worker:

```
docker compose exec encoder php /var/www/html/bin/console messenger:stop-workers
```

This will send a signal to the worker to stop after processing the current message, if any.

# TODOs

- [ ] Test live recordings
- [ ] Make error handling on encoder handler more robust - update /api/media status appropriately.
