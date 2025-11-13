#!/bin/bash

# Setup script for Test Telemetry Plugin
# This script helps set up the test plugin in a WordPress installation

set -e

echo "================================================"
echo "CodeRex Telemetry SDK - Test Plugin Setup"
echo "================================================"
echo ""

# Check if WordPress path is provided
if [ -z "$1" ]; then
    echo "Usage: ./setup.sh /path/to/wordpress"
    echo ""
    echo "Example: ./setup.sh ~/Sites/wordpress"
    echo ""
    exit 1
fi

WP_PATH="$1"
PLUGIN_DIR="$WP_PATH/wp-content/plugins/test-telemetry-plugin"

# Verify WordPress installation
if [ ! -f "$WP_PATH/wp-config.php" ]; then
    echo "Error: WordPress installation not found at $WP_PATH"
    echo "Please provide a valid WordPress installation path."
    exit 1
fi

echo "✓ WordPress installation found"
echo ""

# Check if Composer dependencies are installed
if [ ! -d "../../vendor" ]; then
    echo "Installing Composer dependencies..."
    cd ../..
    composer install
    cd examples/test-plugin
    echo "✓ Composer dependencies installed"
else
    echo "✓ Composer dependencies already installed"
fi
echo ""

# Ask user for installation method
echo "Choose installation method:"
echo "1) Symlink (recommended for development - keeps plugin in SDK repo)"
echo "2) Copy (standalone installation)"
echo ""
read -p "Enter choice [1-2]: " INSTALL_METHOD

if [ "$INSTALL_METHOD" = "1" ]; then
    # Create symlink
    echo "Creating symlink to test plugin..."
    if [ -L "$PLUGIN_DIR" ]; then
        echo "Warning: Symlink already exists. Removing..."
        rm "$PLUGIN_DIR"
    elif [ -d "$PLUGIN_DIR" ]; then
        echo "Warning: Directory exists. Removing..."
        rm -rf "$PLUGIN_DIR"
    fi
    
    CURRENT_DIR=$(pwd)
    ln -s "$CURRENT_DIR" "$PLUGIN_DIR"
    echo "✓ Symlink created successfully"
    echo "  Plugin will use SDK's vendor directory at: ../../vendor/autoload.php"
else
    # Copy plugin files
    echo "Copying test plugin to WordPress..."
    if [ -d "$PLUGIN_DIR" ]; then
        echo "Warning: Plugin directory already exists. Removing old version..."
        rm -rf "$PLUGIN_DIR"
    fi
    
    mkdir -p "$PLUGIN_DIR"
    cp -r ./* "$PLUGIN_DIR/"
    
    echo "✓ Plugin copied successfully"
    echo ""
    echo "Installing Composer dependencies in plugin directory..."
    cd "$PLUGIN_DIR"
    
    # Create a minimal composer.json if it doesn't exist
    if [ ! -f "composer.json" ]; then
        cat > composer.json << 'EOF'
{
    "require": {
        "coderexltd/telemetry": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../../../"
        }
    ]
}
EOF
    fi
    
    composer install --no-dev
    echo "✓ Dependencies installed"
fi
echo ""

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Current PHP version: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '7.4.0', '<') ? 1 : 0);"; then
    echo "✓ PHP version is compatible (7.4+)"
else
    echo "⚠ Warning: PHP version should be 7.4 or higher"
fi
echo ""

# Provide next steps
echo "================================================"
echo "Setup Complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo ""
echo "1. Configure your OpenPanel API key:"
echo "   Edit: $PLUGIN_DIR/test-telemetry-plugin.php"
echo "   Replace: test-api-key-replace-with-real-key"
echo ""
echo "2. Activate the plugin in WordPress admin:"
echo "   Go to: Plugins → Installed Plugins"
echo "   Activate: Test Telemetry Plugin"
echo ""
echo "3. Access the test interface:"
echo "   Go to: Telemetry Test (in admin menu)"
echo ""
echo "4. Follow the testing checklist:"
echo "   See: $PLUGIN_DIR/TESTING-CHECKLIST.md"
echo ""
echo "================================================"
