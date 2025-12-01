# GitHub Upload Guide - PepsiCo CMMS

Complete step-by-step guide to upload your CMMS project to GitHub.

---

## Prerequisites

### 1. Install Git for Windows

**Download & Install:**
1. Go to: https://git-scm.com/download/win
2. Download the latest version
3. Run the installer
4. Use **default settings** during installation
5. **Important:** Restart VS Code after installation

**Verify Installation:**
Open PowerShell and run:
```powershell
git --version
```
You should see something like: `git version 2.43.0.windows.1`

---

## Method 1: VS Code GUI (Easiest - Recommended)

This is the easiest method for beginners.

### Step 1: Initialize Repository

1. In VS Code, click the **Source Control** icon (left sidebar)
   - Icon looks like branches: ⑂
2. Click **"Initialize Repository"** button
3. All your files will appear as changes (green +)

### Step 2: Make First Commit

1. In the Source Control panel, you'll see a text box at the top
2. Type your commit message:
   ```
   Initial commit: CMMS System for PepsiCo Engineering
   
   Features:
   - Work Order Management
   - PM Schedule & Execution
   - Inventory Management
   - Utility Equipment Checklists
   - Performance Dashboard
   - WhatsApp Notifications
   - PWA Mobile Interface
   ```
3. Click the **✓ Commit** button (or press Ctrl+Enter)

### Step 3: Publish to GitHub

1. Click **"Publish to GitHub"** button (appears after commit)
2. VS Code will ask you to sign in to GitHub
   - Click "Allow" when browser opens
   - Sign in to your GitHub account
   - Authorize VS Code
3. Choose repository type:
   - **Private** ← Recommended (only you can see it)
   - Public (anyone can see it)
4. Repository name: `pepsico-cmms-engineering`
5. Click "Publish"
6. Wait for upload to complete (may take a few minutes)
7. **Done!** Your code is now on GitHub

### Step 4: View on GitHub

1. Click "Open on GitHub" button when it appears
2. Or go to: `https://github.com/YOUR_USERNAME/pepsico-cmms-engineering`

---

## Method 2: Command Line (Advanced)

For more control over the process.

### Step 1: Configure Git (First Time Only)

Open PowerShell in your project folder:

```powershell
# Set your name (will appear in commits)
git config --global user.name "Your Name"

# Set your email (must match GitHub email)
git config --global user.email "your.email@example.com"

# Verify configuration
git config --global --list
```

### Step 2: Create GitHub Repository

1. **Go to GitHub:**
   - https://github.com
   - Click "Sign in" (or "Sign up" if you don't have an account)

2. **Create New Repository:**
   - Click the "+" icon (top right)
   - Select "New repository"

3. **Repository Settings:**
   - **Owner:** Your username
   - **Repository name:** `pepsico-cmms-engineering`
   - **Description:** "Computerized Maintenance Management System for PepsiCo Engineering Department"
   - **Visibility:** 
     - ✅ **Private** (Recommended - only you can access)
     - ⬜ Public (Anyone can see)
   - **DO NOT** check any boxes below:
     - ⬜ Add a README file
     - ⬜ Add .gitignore
     - ⬜ Choose a license
   - Click **"Create repository"**

4. **Copy the Repository URL:**
   - You'll see: `https://github.com/YOUR_USERNAME/pepsico-cmms-engineering.git`
   - Keep this page open - you'll need the commands shown

### Step 3: Initialize Local Repository

Open PowerShell in VS Code (Ctrl + `):

```powershell
# Make sure you're in the project directory
cd C:\laragon\www\cmmseng

# Initialize Git repository
git init

# Check status (you should see many untracked files)
git status
```

### Step 4: Add Files to Git

```powershell
# Add all files to staging area
git add .

# Verify files are staged (should show green text)
git status
```

### Step 5: Create First Commit

```powershell
git commit -m "Initial commit: CMMS System for PepsiCo Engineering

Features:
- Work Order Management
- PM Schedule & Execution
- Inventory Management
- Utility Equipment Checklists (Chiller, Compressor, AHU)
- Performance Analysis Dashboard
- WhatsApp Notifications Integration
- PWA Mobile Interface with Barcode Scanner
- Role-based Access Control
- Multi-department Support (Mechanic, Electric, Utility)"
```

### Step 6: Connect to GitHub

Replace `YOUR_USERNAME` with your actual GitHub username:

```powershell
# Add remote repository
git remote add origin https://github.com/YOUR_USERNAME/pepsico-cmms-engineering.git

# Verify remote is added
git remote -v
```

### Step 7: Push to GitHub

```powershell
# Rename branch to 'main'
git branch -M main

# Push to GitHub (first time)
git push -u origin main
```

**If prompted for credentials:**
- Username: Your GitHub username
- Password: Your GitHub **Personal Access Token** (not your password)
  - Get token at: https://github.com/settings/tokens
  - Click "Generate new token (classic)"
  - Select scopes: ✅ repo
  - Copy the token (save it somewhere safe - you won't see it again)

### Step 8: Verify Upload

1. Go to: `https://github.com/YOUR_USERNAME/pepsico-cmms-engineering`
2. You should see all your files
3. The commit message will be displayed

---

## Future Updates

After the initial upload, when you make changes:

### Using VS Code GUI:

1. Make your code changes
2. Go to Source Control panel
3. Review changes (red = deleted, green = added, blue = modified)
4. Type commit message
5. Click ✓ Commit
6. Click "Sync Changes" (↻) button

### Using Command Line:

```powershell
# Check what changed
git status

# See detailed changes
git diff

# Add all changes
git add .

# Commit with message
git commit -m "Added Utility Performance Dashboard with energy metrics"

# Push to GitHub
git push
```

---

## What Gets Uploaded?

### ✅ Included Files:
- All PHP source code
- Blade templates
- JavaScript/CSS assets (source files)
- Database migrations
- Configuration files (except .env)
- Documentation (README, guides)
- Public assets (images, fonts)
- Composer.json (dependency list)
- Package.json (NPM dependencies)

### ❌ Excluded Files (in .gitignore):
- `.env` file (contains passwords)
- `/vendor` folder (Composer dependencies - 70MB+)
- `/node_modules` folder (NPM dependencies - 300MB+)
- `/public/build` (compiled assets)
- `/storage` logs and cache
- Database dumps
- IDE settings (.vscode, .idea)

**Why excluded?**
- Sensitive data (passwords, keys)
- Large files (can be regenerated)
- Local settings (different per developer)

---

## Repository Size Optimization

Your repository should be approximately **10-20 MB** (without vendor/node_modules).

**If upload is slow:**
1. Check `.gitignore` is working:
   ```powershell
   git status
   ```
   Should NOT show: vendor/, node_modules/, .env

2. If you accidentally added large files:
   ```powershell
   # Remove from staging
   git rm -r --cached vendor/
   git rm -r --cached node_modules/
   
   # Commit the removal
   git commit -m "Remove large folders from tracking"
   
   # Push again
   git push
   ```

---

## Cloning on Another Computer

To download your project on another machine:

```powershell
# Clone repository
git clone https://github.com/YOUR_USERNAME/pepsico-cmms-engineering.git

# Navigate to project
cd pepsico-cmms-engineering

# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build
```

---

## Collaboration (Multiple Developers)

### Invite Team Members:

1. Go to repository on GitHub
2. Click "Settings" → "Collaborators"
3. Click "Add people"
4. Enter their GitHub username/email
5. They'll receive an invitation

### Working Together:

**Before you start coding:**
```powershell
# Get latest changes
git pull
```

**After making changes:**
```powershell
# Add and commit
git add .
git commit -m "Your changes"

# Get any new changes from others
git pull

# Resolve conflicts if any
# Then push your changes
git push
```

---

## Branching Strategy (Optional)

For larger teams or production deployments:

### Create Feature Branch:
```powershell
# Create and switch to new branch
git checkout -b feature/utility-dashboard

# Make your changes
# Commit changes
git add .
git commit -m "Added utility performance dashboard"

# Push branch to GitHub
git push -u origin feature/utility-dashboard
```

### Merge to Main:
1. Go to GitHub repository
2. Click "Pull requests" → "New pull request"
3. Select your feature branch
4. Click "Create pull request"
5. Review changes
6. Click "Merge pull request"

---

## Common Issues & Solutions

### Issue 1: "git not recognized"
**Solution:** 
- Install Git from https://git-scm.com/download/win
- Restart VS Code and PowerShell

### Issue 2: "Permission denied" when pushing
**Solution:** 
- Use Personal Access Token instead of password
- Generate at: https://github.com/settings/tokens

### Issue 3: "Large files" warning
**Solution:** 
- Check .gitignore is working
- Remove vendor/ and node_modules/ from tracking

### Issue 4: "Nothing to commit"
**Solution:** 
- Files already committed
- Use `git status` to check

### Issue 5: "Merge conflict"
**Solution:** 
```powershell
# Pull latest changes
git pull

# Open conflicted files in VS Code
# Look for markers: <<<<<<<, =======, >>>>>>>
# Choose which code to keep
# Remove the markers

# After resolving
git add .
git commit -m "Resolved merge conflicts"
git push
```

### Issue 6: "Remote already exists"
**Solution:** 
```powershell
# Remove existing remote
git remote remove origin

# Add correct remote
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
```

---

## Best Practices

### ✅ Do:
- Commit often with clear messages
- Pull before you push
- Review changes before committing
- Use meaningful branch names
- Keep .gitignore updated
- Never commit .env file
- Write descriptive commit messages

### ❌ Don't:
- Commit sensitive data (passwords, keys)
- Commit generated files (vendor/, node_modules/)
- Make huge commits (split into smaller ones)
- Force push (`git push -f`) on shared branches
- Commit directly to main (use branches)
- Ignore merge conflicts

---

## Commit Message Guidelines

### Good Commit Messages:
```
✅ "Added Utility Performance Dashboard with energy metrics and compliance tracking"
✅ "Fixed CSRF token issue in login authentication"
✅ "Updated WhatsApp notification service to support group messages"
✅ "Optimized PM compliance query performance"
```

### Bad Commit Messages:
```
❌ "update"
❌ "fix bug"
❌ "changes"
❌ "asdfasdf"
```

### Format:
```
Short summary (50 chars or less)

Detailed explanation of what changed and why.
Can include multiple lines.

- Bullet points for multiple changes
- Reference issue numbers if applicable
```

---

## Security Checklist

Before uploading, verify:

- [ ] `.env` file is NOT tracked (in .gitignore)
- [ ] Database passwords are NOT in code
- [ ] API keys are NOT in code
- [ ] Private repository is selected
- [ ] Sensitive comments removed
- [ ] Debug mode references removed from docs
- [ ] Production credentials not in migrations/seeders

---

## Additional Resources

### Official Documentation:
- Git Basics: https://git-scm.com/book/en/v2/Getting-Started-Git-Basics
- GitHub Docs: https://docs.github.com/en/get-started
- VS Code Git: https://code.visualstudio.com/docs/sourcecontrol/overview

### Useful Commands Cheat Sheet:
```powershell
# Status & Info
git status              # Check current status
git log                 # View commit history
git log --oneline       # Compact history
git diff                # See changes

# Branches
git branch              # List branches
git branch feature-name # Create branch
git checkout main       # Switch to main
git checkout -b new     # Create and switch

# Undo Changes
git restore file.php    # Discard changes
git reset HEAD~1        # Undo last commit
git revert <commit>     # Revert specific commit

# Remote
git remote -v           # List remotes
git fetch               # Download changes
git pull                # Download and merge
git push                # Upload changes
```

---

## Next Steps

After uploading to GitHub:

1. **Add README.md** - Project description for GitHub homepage
2. **Add LICENSE** - Choose appropriate license
3. **Setup CI/CD** - Automated testing (optional)
4. **Enable Branch Protection** - Protect main branch
5. **Add Issue Templates** - For bug reports/features
6. **Wiki Documentation** - Extended documentation

---

## Support

If you need help:
1. Check this guide first
2. Search GitHub Docs
3. Check VS Code Git documentation
4. Ask in GitHub Discussions (your repository)

---

**Created:** December 2025  
**Last Updated:** December 2025  
**Project:** PepsiCo CMMS Engineering System
