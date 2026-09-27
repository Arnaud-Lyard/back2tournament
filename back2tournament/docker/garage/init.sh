#!/bin/sh
# Prepares Garage for the application through its admin API: a layout of one
# node, the key the API signs its requests with, the bucket the images go
# to, and that bucket served on Garage's web endpoint under the host of
# S3_PUBLIC_URL. Running it again changes nothing.
#
# Reads GARAGE_ADMIN_URL (http://garage:3903 by default), GARAGE_ADMIN_TOKEN,
# S3_BUCKET, S3_ACCESS_KEY_ID, S3_SECRET_ACCESS_KEY and S3_PUBLIC_URL.
set -eu

admin="${GARAGE_ADMIN_URL:-http://garage:3903}"
auth="Authorization: Bearer ${GARAGE_ADMIN_TOKEN}"

# call METHOD ENDPOINT [JSON]: the answer, or a failure on an HTTP error.
call() {
	curl -fsS -X "$1" -H "$auth" -H 'Content-Type: application/json' "$admin/v2/$2" ${3:+--data "$3"}
}

# found ENDPOINT: whether a GET answers 200.
found() {
	[ "$(curl -s -o /dev/null -w '%{http_code}' -H "$auth" "$admin/v2/$1")" = 200 ]
}

# first_id JSON: the first "id" it holds.
first_id() {
	grep -o '"id": *"[0-9a-f]*"' | head -n 1 | cut -d '"' -f 4
}

echo 'Waiting for Garage...'
until found GetClusterStatus; do sleep 1; done

if call GET GetClusterStatus | grep -q '"layoutVersion": *0,'; then
	node=$(call GET GetClusterStatus | first_id)
	call POST UpdateClusterLayout "{\"roles\":[{\"id\":\"$node\",\"zone\":\"dc1\",\"capacity\":10000000000,\"tags\":[]}]}" > /dev/null
	call POST ApplyClusterLayout '{"version":1}' > /dev/null
	echo "Layout applied on node $node."
fi
until [ "$(curl -s -o /dev/null -w '%{http_code}' "$admin/health")" = 200 ]; do sleep 1; done

if ! found "GetKeyInfo?id=$S3_ACCESS_KEY_ID"; then
	call POST ImportKey "{\"accessKeyId\":\"$S3_ACCESS_KEY_ID\",\"secretAccessKey\":\"$S3_SECRET_ACCESS_KEY\",\"name\":\"back2tournament\"}" > /dev/null
	echo "Key $S3_ACCESS_KEY_ID imported."
fi

if ! found "GetBucketInfo?globalAlias=$S3_BUCKET"; then
	call POST CreateBucket "{\"globalAlias\":\"$S3_BUCKET\"}" > /dev/null
	echo "Bucket $S3_BUCKET created."
fi
bucket=$(call GET "GetBucketInfo?globalAlias=$S3_BUCKET" | first_id)

call POST AllowBucketKey "{\"bucketId\":\"$bucket\",\"accessKeyId\":\"$S3_ACCESS_KEY_ID\",\"permissions\":{\"read\":true,\"write\":true,\"owner\":true}}" > /dev/null

# The web endpoint serves a bucket for the host names it is aliased to.
host=$(echo "$S3_PUBLIC_URL" | sed -E 's#^[a-z]+://##; s#[:/].*$##')
call POST "UpdateBucket?id=$bucket" '{"websiteAccess":{"enabled":true,"indexDocument":"index.html","errorDocument":null}}' > /dev/null
call POST AddBucketAlias "{\"bucketId\":\"$bucket\",\"globalAlias\":\"$host\"}" > /dev/null

echo "Garage is ready: the bucket $S3_BUCKET is written with the key $S3_ACCESS_KEY_ID and served on $S3_PUBLIC_URL."
