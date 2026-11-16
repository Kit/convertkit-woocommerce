# Remove vendor directory
cd ..
rm -rf vendor

# Run composer to only install non-dev dependencies
composer install --no-dev

# Build ZIP file, excluding non-Plugin files
[ -e convertkit-for-woocommerce.zip ] && rm convertkit-for-woocommerce.zip
zip -r convertkit-for-woocommerce.zip . \
-x "*.git*" \
-x ".devcontainer/*" \
-x ".scripts/*" \
-x ".wordpress-org/*" \
-x "log/*" \
-x "log.txt" \
-x "node_modules/*" \
-x "package.json" \
-x "package-lock.json" \
-x ".stylelintrc.json" \
-x ".eslintrc.js" \
-x "tests/*" \
-x "vendor/composer/*" \
-x "vendor/convertkit/convertkit-wordpress-libraries/.github" \
-x "vendor/convertkit/convertkit-wordpress-libraries/tests/*" \
-x "vendor/convertkit/convertkit-wordpress-libraries/composer.json" \
-x "vendor/autoload.php" \
-x "*.distignore" \
-x "*.env.*" \
-x ".gitignore" \
-x "*.md" \
-x "*.yml" \
-x "composer.json" \
-x "composer.lock" \
-x "*.xml" \
-x "*.neon" \
-x "*.dist" \
-x "*.example" \
-x "*.DS_Store" \

# Run composer to install dev dependencies, returning enviornment back to original state
composer update