#!/bin/bash
# refresh_view.sh

# Connect to PostgreSQL and refresh the materialized view
psql "$DATABASE_URL" -c "REFRESH MATERIALIZED VIEW CONCURRENTLY template_search_view;"
