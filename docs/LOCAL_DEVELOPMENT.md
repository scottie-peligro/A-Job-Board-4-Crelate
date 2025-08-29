# Local Development Guide

This guide covers setting up local development for the Crelate Job Board plugin, including tunneling solutions for testing API callbacks and webhooks.

## Prerequisites

- WordPress development environment (Local by Flywheel, XAMPP, etc.)
- PHP 8.0+
- Node.js (for asset building)
- Git
- Cloudflare Tunnel or ngrok (for tunneling)

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd crelate-job-board-plugin
   ```

2. **Set up local WordPress**
   - Use Local by Flywheel, XAMPP, or your preferred method
   - Ensure your site runs on `http://localhost:5210`

3. **Install the plugin**
   - Copy the plugin files to `wp-content/plugins/crelate-job-board-plugin/`
   - Activate the plugin in WordPress admin

4. **Configure API settings**
   - Go to WordPress Admin → Crelate Job Board → Settings
   - Enter your Crelate API key and portal ID
   - Save settings

5. **Set up tunneling** (see below)

## Tunneling Solutions

### Option 1: Cloudflare Tunnel (Recommended)

Cloudflare Tunnel provides a secure, reliable tunnel without exposing your local machine to the internet.

#### Installation

**macOS:**
```bash
brew install cloudflare/cloudflare/cloudflared
```

**Linux:**
```bash
# Download from https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
```

**Windows:**
Download from the [official website](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/)

#### Setup

1. **Use the provided script:**
   ```bash
   chmod +x scripts/setup-tunnel.sh
   ./scripts/setup-tunnel.sh setup
   ```

2. **Manual setup:**
   ```bash
   # Create tunnel
   cloudflared tunnel create crelate-local-dev
   
   # Get tunnel ID
   cloudflared tunnel list
   
   # Create config file
   mkdir -p ~/.cloudflared
   cat > ~/.cloudflared/config.yml << EOF
   tunnel: <TUNNEL_ID>
   credentials-file: ~/.cloudflared/<TUNNEL_ID>.json
   
   ingress:
     - hostname: crelate-local-dev.your-domain.com
       service: http://localhost:5210
     - service: http_status:404
   EOF
   
   # Start tunnel
   cloudflared tunnel run crelate-local-dev
   ```

#### Usage

```bash
# Start tunnel
./scripts/setup-tunnel.sh start

# Check status
./scripts/setup-tunnel.sh status

# Stop tunnel
./scripts/setup-tunnel.sh stop
```

### Option 2: ngrok

ngrok is a popular alternative that's easier to set up but less secure.

#### Installation

```bash
# Download from https://ngrok.com/download
# Or use package manager
brew install ngrok  # macOS
```

#### Setup

1. **Sign up for free account** at https://ngrok.com
2. **Get your authtoken** from the dashboard
3. **Configure ngrok:**
   ```bash
   ngrok config add-authtoken <YOUR_TOKEN>
   ```

#### Usage

```bash
# Start tunnel
ngrok http 5210

# Or with custom subdomain (requires paid plan)
ngrok http 5210 --subdomain=crelate-local-dev
```

## Configuration

### Environment Variables

Create a `.env.local` file in your project root:

```env
# Public URL for callbacks (set by tunnel script)
PUBLIC_BASE_URL=https://your-tunnel-url.com

# Local development settings
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false

# Crelate API settings (for testing)
CRELATE_API_KEY=your_test_api_key
CRELATE_PORTAL_ID=your_portal_id
```

### WordPress Configuration

Add to your `wp-config.php`:

```php
// Enable debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Load local environment variables
if (file_exists(__DIR__ . '/.env.local')) {
    $env = parse_ini_file(__DIR__ . '/.env.local');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

## Testing

### WP-CLI Commands

The plugin includes several WP-CLI commands for testing:

```bash
# Test API connectivity
wp crelate:net-test

# Test submission
wp crelate:test --email=test@example.com

# Test with resume
wp crelate:test --email=test@example.com --resume=/path/to/resume.pdf

# Test with job linking
wp crelate:test --email=test@example.com --job-id=12345

# Show configuration
wp crelate:config

# View logs
wp crelate:logs --lines=100

# Clear logs
wp crelate:clear-logs

# Test Gravity Forms integration
wp crelate:test-gf --form-id=1
```

### Admin Debug Page

1. Go to WordPress Admin → Crelate Job Board → Debug
2. Check API status and connection
3. Test submissions with sample data
4. View recent logs
5. Download logs for analysis

### Manual Testing

1. **Test form submission:**
   - Create a test job posting
   - Submit an application through the form
   - Check logs for success/errors

2. **Test API callbacks:**
   - Use your tunnel URL in Crelate webhook settings
   - Trigger a webhook from Crelate
   - Check logs for callback processing

3. **Test resume upload:**
   - Submit a form with a resume file
   - Verify file upload to Crelate
   - Check file attachment in Crelate dashboard

## Troubleshooting

### Common Issues

#### 1. Tunnel Connection Issues

**Problem:** Tunnel won't start or connect
**Solutions:**
- Check if port 5210 is available: `lsof -i :5210`
- Verify cloudflared/ngrok installation
- Check firewall settings
- Try different port: `LOCAL_PORT=3000 ./scripts/setup-tunnel.sh setup`

#### 2. API Authentication Errors

**Problem:** 401/403 errors from Crelate API
**Solutions:**
- Verify API key in WordPress settings
- Check API key permissions in Crelate
- Ensure portal ID is correct
- Test with `wp crelate:net-test`

#### 3. File Upload Issues

**Problem:** Resume uploads fail
**Solutions:**
- Check file permissions in uploads directory
- Verify file size limits in PHP settings
- Check Crelate API file upload limits
- Test with smaller files first

#### 4. Gravity Forms Integration Issues

**Problem:** Forms don't submit to Crelate
**Solutions:**
- Verify form has Crelate fields configured
- Check if form is enabled for Crelate integration
- Review field mappings in form editor
- Test with `wp crelate:test-gf --form-id=1`

### Debug Steps

1. **Check logs:**
   ```bash
   wp crelate:logs --level=ERROR
   tail -f wp-content/uploads/crelate-logs/crelate.log
   ```

2. **Test network connectivity:**
   ```bash
   wp crelate:net-test
   ```

3. **Verify configuration:**
   ```bash
   wp crelate:config
   ```

4. **Check WordPress debug log:**
   ```bash
   tail -f wp-content/debug.log
   ```

### Performance Optimization

1. **Enable caching:**
   - Install Redis or Memcached
   - Configure object caching
   - Enable page caching

2. **Optimize database:**
   - Regular database cleanup
   - Optimize tables
   - Archive old logs

3. **Monitor resources:**
   - Check memory usage
   - Monitor API response times
   - Track submission success rates

## Development Workflow

### 1. Local Development

```bash
# Start local environment
./scripts/setup-tunnel.sh start

# Make changes to code
# Test with WP-CLI commands
wp crelate:test --email=test@example.com

# Check logs for issues
wp crelate:logs --lines=50
```

### 2. Testing

```bash
# Run comprehensive tests
wp crelate:net-test
wp crelate:test --email=test@example.com --resume=/path/to/test.pdf
wp crelate:test-gf --form-id=1

# Check all systems
wp crelate:config
```

### 3. Deployment

```bash
# Deploy to staging
# Use GitHub Actions or manual deployment

# Test on staging
# Verify all functionality works

# Deploy to production
# Monitor logs and performance
```

## Security Considerations

1. **API Keys:**
   - Never commit API keys to version control
   - Use environment variables for sensitive data
   - Rotate keys regularly

2. **Tunneling:**
   - Cloudflare Tunnel is more secure than ngrok
   - Use authentication for tunnel access
   - Monitor tunnel usage

3. **File Uploads:**
   - Validate file types and sizes
   - Scan uploaded files for malware
   - Store files securely

4. **Logs:**
   - PII is automatically masked in logs
   - Regularly rotate log files
   - Secure log storage

## Support

For issues and questions:

1. Check this documentation
2. Review troubleshooting section
3. Check plugin logs
4. Test with WP-CLI commands
5. Contact development team

## Additional Resources

- [Crelate API Documentation](https://api.crelate.com/)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [ngrok Documentation](https://ngrok.com/docs)
