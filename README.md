# cs370-IMDb-clone
Database project - IMDb Clone

---

## Local Development Setup

### 1a. PHP Installation (Windows)
1. Download x64 Thread Safe zip from windows.php.net.
2. Unzip to `C:\Program Files\PHP`.
3. Rename `php.ini-development` to `php.ini`.
4. Edit `php.ini` to uncomment:
   * `extension_dir = "ext"`
   * `extension=mysqli`

### 1b. PHP Installation (macOS)
1. PHP is no longer pre-installed on macOS Monterey and later. Install via Homebrew: `brew install php`.
2. The configuration file is typically located at `/usr/local/etc/php/8.x/php.ini` (Intel) or `/opt/homebrew/etc/php/8.x/php.ini` (Apple Silicon).
3. Ensure the `mysqli` extension is enabled in the configuration.
4. Verify installation by running `php -v` in the terminal.

### 2. Database Setup
1. Install MySQL Community Server and MySQL Workbench.
2. Execute the script in `/sql/schema.sql` (***coming soon***) to initialize the database.
3. Create a user matching the schema name.
4. Grant `SELECT, INSERT, UPDATE, DELETE` permissions to that user.

### 3. IDE Configuration (PhpStorm)
1. Open the project.
2. Navigate to File > Settings (Windows) or PhpStorm > Settings (macOS).
3. Go to Languages & Frameworks > PHP.
4. Set the CLI Interpreter to the path of your `php` executable.

---

## Repository Structure
* `/sql`: Database schema scripts and ERD exports.
* `/data`: Source CSV files for ETL operations.
