# Vid Cruncher!

A system for receiving video files, splitting them into chunks (if necessary), and re-encoding them into AV1 across
multiple encoding machines.  

# Setup

## Coordinator

This run an API and database to coordinate actions.  To get running:

1. Create `.env.local` and populate the following values:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@db:5432/app?serverVersion=14&charset=utf8"

# URL to the coordinator - used for downloading and uploading video data and generating URLs
COORDINATOR_BASE_URL=http://10.0.0.79:8000
```

2. `composer install` - to install the vendor libs  # Requires PHP and composer to be installed on the host
3. `bin/console doc:mig:mig` - to setup the database # Requires PHP and composer to be installed on the host. 
4. `docker compose up -d` - Will build and start the web and db containers.

## Encoder

For each worker machine, do the following.

1. Create `.env.local` and populate the following values:

```dotenv
# Populate with the IP of the machine running the coordinator - The database is used for symfony messenger to pick up
# jobs, but otherwise doesn't use it. (The API is used for all other communications)
# FUTURE THOUGHT: Could use API via polling to read jobs?  Alternatively, could use rabbit as a transport
DATABASE_URL="postgresql://app:!ChangeMe!@10.0.0.79:5432/app?serverVersion=14&charset=utf8"

# URL to the coordinator - used for downloading and uploading video data
COORDINATOR_BASE_URL=http://10.0.0.79:8000
```

2. `docker-compose -f docker-compose-encoder.yml up --build -d`

# Usage

By default, any files dropped into public/videos/recordings will get picked up for processing.  

For live-recordings, set files to be dropped into public/videos/live-recordings.  They will be picked up for processing
2 minutes after their last modified time to avoid picking up a file too soon.  The fully-assembled result will be created
10 minutes after the last file's modified time, to ensure all files are captured.  

After files are processed, their fully-assembed results are placed in public/videos/done


# TODOs

- [ ] Build assembler handler
- [ ] Build assembler decision into cron
- [ ] Build docker container that runs the cron
- [ ] Alter cron to block and run once per minute
- [ ] Test live recordings
