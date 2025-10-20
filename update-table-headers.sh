#!/bin/bash
# Update table header gradients to green

sed -i '' 's/from-purple-600 to-purple-700/from-green-600 to-green-700/g' medical-records.php
sed -i '' 's/from-indigo-600 to-indigo-700/from-green-600 to-green-700/g' performance-reviews.php user-management.php
sed -i '' 's/from-teal-600 to-teal-700/from-green-600 to-green-700/g' regularization-criteria.php
sed -i '' 's/from-blue-600 to-blue-700/from-green-600 to-green-700/g' dtr-management.php
sed -i '' 's/from-cyan-600 to-cyan-700/from-green-600 to-green-700/g' leave-reports.php
sed -i '' 's/from-emerald-600 to-emerald-700/from-green-600 to-green-700/g' leave-allowance-management.php

echo "✅ All table headers updated to green gradient"
