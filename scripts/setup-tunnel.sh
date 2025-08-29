#!/bin/bash

# Crelate Local Development Tunnel Setup Script
# This script helps set up Cloudflare Tunnel for local development

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOCAL_PORT=${LOCAL_PORT:-5210}
TUNNEL_NAME="crelate-local-dev"
CONFIG_FILE=".env.local"

echo -e "${BLUE}=== Crelate Local Development Tunnel Setup ===${NC}"
echo ""

# Check if cloudflared is installed
check_cloudflared() {
    if ! command -v cloudflared &> /dev/null; then
        echo -e "${RED}❌ cloudflared is not installed${NC}"
        echo ""
        echo "Please install cloudflared:"
        echo "  macOS: brew install cloudflare/cloudflare/cloudflared"
        echo "  Linux: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
        echo "  Windows: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/"
        exit 1
    fi
    
    echo -e "${GREEN}✅ cloudflared is installed${NC}"
}

# Check if tunnel exists
check_tunnel() {
    echo -e "${BLUE}Checking for existing tunnel...${NC}"
    
    if cloudflared tunnel list | grep -q "$TUNNEL_NAME"; then
        echo -e "${GREEN}✅ Tunnel '$TUNNEL_NAME' exists${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️  Tunnel '$TUNNEL_NAME' not found${NC}"
        return 1
    fi
}

# Create tunnel
create_tunnel() {
    echo -e "${BLUE}Creating tunnel '$TUNNEL_NAME'...${NC}"
    
    # Create tunnel
    cloudflared tunnel create "$TUNNEL_NAME"
    
    # Get tunnel ID
    TUNNEL_ID=$(cloudflared tunnel list | grep "$TUNNEL_NAME" | awk '{print $1}')
    
    echo -e "${GREEN}✅ Tunnel created with ID: $TUNNEL_ID${NC}"
    
    # Create config file
    create_config_file "$TUNNEL_ID"
}

# Create config file
create_config_file() {
    local tunnel_id=$1
    
    echo -e "${BLUE}Creating tunnel configuration...${NC}"
    
    cat > "~/.cloudflared/config.yml" << EOF
tunnel: $tunnel_id
credentials-file: ~/.cloudflared/$tunnel_id.json

ingress:
  - hostname: crelate-local-dev.your-domain.com
    service: http://localhost:$LOCAL_PORT
  - service: http_status:404
EOF
    
    echo -e "${GREEN}✅ Configuration file created${NC}"
}

# Start tunnel
start_tunnel() {
    echo -e "${BLUE}Starting tunnel...${NC}"
    
    # Check if tunnel is already running
    if pgrep -f "cloudflared.*tunnel.*run" > /dev/null; then
        echo -e "${YELLOW}⚠️  Tunnel is already running${NC}"
        return 0
    fi
    
    # Start tunnel in background
    nohup cloudflared tunnel run "$TUNNEL_NAME" > tunnel.log 2>&1 &
    TUNNEL_PID=$!
    
    echo -e "${GREEN}✅ Tunnel started with PID: $TUNNEL_PID${NC}"
    echo "Logs are being written to tunnel.log"
    
    # Wait a moment for tunnel to start
    sleep 3
    
    # Check if tunnel started successfully
    if kill -0 $TUNNEL_PID 2>/dev/null; then
        echo -e "${GREEN}✅ Tunnel is running${NC}"
    else
        echo -e "${RED}❌ Failed to start tunnel${NC}"
        echo "Check tunnel.log for details"
        exit 1
    fi
}

# Get tunnel URL
get_tunnel_url() {
    echo -e "${BLUE}Getting tunnel URL...${NC}"
    
    # Wait for tunnel to be ready
    echo "Waiting for tunnel to be ready..."
    sleep 5
    
    # Try to get the URL from the logs
    if [ -f "tunnel.log" ]; then
        TUNNEL_URL=$(grep -o "https://.*trycloudflare.com" tunnel.log | head -1)
        if [ -n "$TUNNEL_URL" ]; then
            echo -e "${GREEN}✅ Tunnel URL: $TUNNEL_URL${NC}"
            
            # Save to .env.local
            echo "PUBLIC_BASE_URL=$TUNNEL_URL" > "$CONFIG_FILE"
            echo -e "${GREEN}✅ Saved to $CONFIG_FILE${NC}"
            
            return 0
        fi
    fi
    
    echo -e "${YELLOW}⚠️  Could not determine tunnel URL automatically${NC}"
    echo "Please check tunnel.log for the URL"
    return 1
}

# Stop tunnel
stop_tunnel() {
    echo -e "${BLUE}Stopping tunnel...${NC}"
    
    # Find and kill tunnel process
    TUNNEL_PID=$(pgrep -f "cloudflared.*tunnel.*run")
    if [ -n "$TUNNEL_PID" ]; then
        kill $TUNNEL_PID
        echo -e "${GREEN}✅ Tunnel stopped${NC}"
    else
        echo -e "${YELLOW}⚠️  No tunnel process found${NC}"
    fi
}

# Show status
show_status() {
    echo -e "${BLUE}=== Tunnel Status ===${NC}"
    
    # Check if tunnel exists
    if check_tunnel; then
        echo -e "${GREEN}✅ Tunnel exists${NC}"
    else
        echo -e "${RED}❌ Tunnel does not exist${NC}"
        return 1
    fi
    
    # Check if tunnel is running
    if pgrep -f "cloudflared.*tunnel.*run" > /dev/null; then
        echo -e "${GREEN}✅ Tunnel is running${NC}"
        
        # Show tunnel URL
        if [ -f "$CONFIG_FILE" ]; then
            PUBLIC_URL=$(grep "PUBLIC_BASE_URL" "$CONFIG_FILE" | cut -d'=' -f2)
            if [ -n "$PUBLIC_URL" ]; then
                echo -e "${GREEN}✅ Public URL: $PUBLIC_URL${NC}"
            fi
        fi
    else
        echo -e "${RED}❌ Tunnel is not running${NC}"
    fi
    
    # Show local port
    echo -e "${BLUE}Local port: $LOCAL_PORT${NC}"
}

# Show help
show_help() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  setup     - Set up tunnel (create if needed, start)"
    echo "  start     - Start existing tunnel"
    echo "  stop      - Stop tunnel"
    echo "  status    - Show tunnel status"
    echo "  help      - Show this help"
    echo ""
    echo "Environment variables:"
    echo "  LOCAL_PORT - Local port to tunnel (default: 5210)"
    echo ""
    echo "Examples:"
    echo "  $0 setup"
    echo "  LOCAL_PORT=3000 $0 setup"
    echo "  $0 status"
}

# Main script
main() {
    case "${1:-help}" in
        setup)
            check_cloudflared
            if ! check_tunnel; then
                create_tunnel
            fi
            start_tunnel
            get_tunnel_url
            show_status
            ;;
        start)
            check_cloudflared
            if check_tunnel; then
                start_tunnel
                get_tunnel_url
            else
                echo -e "${RED}❌ Tunnel does not exist. Run '$0 setup' first.${NC}"
                exit 1
            fi
            ;;
        stop)
            stop_tunnel
            ;;
        status)
            show_status
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            echo -e "${RED}❌ Unknown command: $1${NC}"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
