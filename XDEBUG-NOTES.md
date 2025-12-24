# Xdebug Configuration Notes

## Xdebug Status (LocalWP)
- **Version**: 3.3.0
- **Mode**: debug,develop
- **Enabled Features**:
  - Development Helpers: ✔ enabled
  - Step Debugger: ✔ enabled
- **PHP Version**: 8.3.23 (Run Time), 8.3.0 (Compile Time)
- **Configuration File**: C:\Users\rafae\AppData\Roaming\Local\run\S7Met_Ajk\conf\php\php.ini
- **Key Settings**:
  - xdebug.mode: debug,develop
  - xdebug.start_with_request: yes
  - xdebug.client_host: localhost
  - xdebug.client_port: 9003
  - xdebug.output_dir: C:\Windows\Temp

## Usage for Apollo Plugins Debugging
- **IDE Setup**: Use VS Code with PHP Debug extension. Set breakpoints in plugin files.
- **Trigger**: Xdebug starts automatically on requests.
- **Step Debugging**: Active for tracing fatal errors, taxonomy issues, etc.
- **Logs**: Check C:\Windows\Temp for profiler/tracer outputs if enabled.
- **Testing**: With WP_DEBUG enabled, Xdebug will provide detailed stack traces for any errors during plugin activation.

## Notes
- Xdebug is active and ready for debugging the Apollo ecosystem.
- Use for identifying exact points of failure in the 4-plugin integration.
