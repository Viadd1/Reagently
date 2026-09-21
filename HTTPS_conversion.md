# Enabling HTTPS on MAMP (Windows)
 
 
Paths below use `D:\MAMP` as an example. Replace it with your own MAMP folder everywhere.
 
---
 
## 1. Find your MAMP folder
 
Press the Windows key, type `MAMP`, right-click the app and choose **Open file location** (repeat on the shortcut if needed). The MAMP folder contains `bin`, `conf`, `htdocs` and `logs`.
 
## 2. Open PowerShell and set a path variable
 
```powershell
$mamp = "D:\MAMP"
Test-Path "$mamp\conf\apache"
```
 
The second line must print `True`.
 
> The variable is lost when you close PowerShell. If you open a new window, set `$mamp` again.
## 3. Create a small OpenSSL config file
 
Paste this whole block starting with @":
 
```powershell
@"
[req]
distinguished_name = dn
x509_extensions = v3_req
prompt = no
default_md = sha256
 
[dn]
CN = localhost
 
[v3_req]
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt
 
[alt]
DNS.1 = localhost
IP.1 = 127.0.0.1
"@ | Set-Content -Encoding ascii "$mamp\conf\apache\san.cnf"
```
 
Check that the file exists by pasting the command in powershell:
 
```powershell
Get-Content "$mamp\conf\apache\san.cnf"
```
You should see the config text printed back

## 4. Generate the certificate and private key

Paste the code as it is:  
```powershell
cd "$mamp\bin\apache\bin"
.\openssl.exe req -x509 -nodes -days 365 -newkey rsa:2048 -keyout "$mamp\conf\apache\localhost-key.pem" -out "$mamp\conf\apache\localhost.pem" -config "$mamp\conf\apache\san.cnf" -extensions v3_req
```
 
These two warnings are harmless:
 
- `WARNING: can't open config file: /usr/local/ssl/openssl.cnf`
- `unable to write 'random state'`
This creates `localhost.pem` (certificate) and `localhost-key.pem` (private key) in `$mamp\conf\apache\`. The certificate is valid for 365 days.
 
## 5. Verify the certificate
 
```powershell
.\openssl.exe x509 -in "$mamp\conf\apache\localhost.pem" -noout -text | Select-String -Pattern "Alternative" -Context 0,1
```
 
The output must contain `DNS:localhost, IP Address:127.0.0.1`. Without this, Chrome and Edge reject the certificate.
 
## 6. Make Windows trust the certificate
 
```powershell
certutil -addstore -user Root "$mamp\conf\apache\localhost.pem"
```
 
Click **Yes** on the security warning. It should end with `CertUtil: -addstore command completed successfully`.
 
## 7. Find the active `httpd.conf` and your DocumentRoot
 
MAMP has several copies of `httpd.conf`. The one Apache actually uses is in `conf\apache`, **not** in `bin\apache\conf`. Ignore the timestamped backups and the `original` folder.
 
Just run the code:
```powershell
Select-String -Path "$mamp\conf\apache\httpd.conf" -Pattern "^DocumentRoot"
Select-String -Path "$mamp\conf\apache\httpd.conf" -Pattern "ssl|^Listen"
```
 
- The first command shows your **DocumentRoot**, the folder MAMP serves. On our laptops this is the project folder, so **each person's path is different**. Write down the path.
- The second command should show `LoadModule ssl_module modules/mod_ssl.so` **without a `#` at the start**. If it is commented out, remove the `#`.
## 8. Add the HTTPS block to `httpd.conf`
 
```powershell
notepad "$mamp\conf\apache\httpd.conf"
```
 
Scroll to the very bottom of the file, then on a new line paste this block **once**:
 
```apache
Listen 443
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot "PASTE-YOUR-DOCUMENTROOT-HERE"
    SSLEngine on
    SSLCertificateFile "D:/MAMP/conf/apache/localhost.pem"
    SSLCertificateKeyFile "D:/MAMP/conf/apache/localhost-key.pem"
</VirtualHost>
```
 
Replace:
 
- `PASTE-YOUR-DOCUMENTROOT-HERE` with the path from step 7, **using forward slashes** and keeping the quotes (our path contains spaces). Example: `D:/Bioinformatics_Uni_Uppsala/Year 2/IMS/IMS_project_Dory`
- `D:/MAMP` with your own MAMP folder, also with forward slashes.
Save with **Ctrl+S**.
 
## 9. Check the syntax
Run:
```powershell
cd "$mamp\bin\apache\bin"
.\httpd.exe -t -f "$mamp\conf\apache\httpd.conf"
```
 
It should end with `Syntax OK`. Warnings about `ServerName` are harmless. Fix any real error before continuing.
 
## 10. Restart MAMP and test
 
1. In the MAMP window click **Stop Servers**, then **Start Servers**.
2. Close all browser windows, reopen the browser, and go to **https://localhost**.
---
 
 
## What HTTPS does and does not protect
 
- **Protects:** data in transit between browser and server.
- **Does not protect:** data after the server decrypts it (SQL injection, XSS, weak passwords, database leaks).
- On `localhost` the traffic never leaves the computer, so here doing the conversion is not necessary. We use it to reproduce a production-like setup.
 