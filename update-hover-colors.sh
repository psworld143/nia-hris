#!/bin/bash
# Update hover effects to green

sed -i '' 's/hover:bg-purple-50/hover:bg-green-50/g' medical-records.php
sed -i '' 's/hover:bg-indigo-50/hover:bg-green-50/g' performance-reviews.php user-management.php
sed -i '' 's/hover:bg-teal-50/hover:bg-green-50/g' regularization-criteria.php
sed -i '' 's/hover:bg-blue-50/hover:bg-green-50/g' dtr-management.php
sed -i '' 's/hover:bg-cyan-50/hover:bg-green-50/g' leave-reports.php
sed -i '' 's/hover:bg-emerald-50/hover:bg-green-50/g' leave-allowance-management.php

echo "✅ All hover effects updated to green"
