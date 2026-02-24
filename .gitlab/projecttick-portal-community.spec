Name:           projecttick-portal-ce
Version:        1.0.5
Release:        1%{?dist}
Summary:        Project Tick Portal Community Edition (Native Runtime)

License:        MIT
URL:            https://projecttick.org/
Source0:        projt-portal.tar.gz

BuildArch:      x86_64
Prefix:         /opt/projt-portal

%description
Project Tick Portal Community Edition Self-Contained Engine.
Includes fully optimized bundled PHP, an independent Nginx server,
and MariaDB databases, managed by projecttick-ctl.

%prep
# Extract quietly and wrap in a folder matching the buildroot style
%setup -q -c

%install
mkdir -p %{buildroot}/opt/projt-portal
# Copy everything extracted to the dest dir (from opt/projt-portal)
cp -ra opt/projt-portal/* %{buildroot}/opt/projt-portal/

%post
# Runs after finishing laying out the RPM onto the system
if [ -f /opt/projt-portal/setup_rhel.sh ]; then
   chmod +x /opt/projt-portal/setup_rhel.sh
   # Symlink projecttick-ctl to global path on install
   ln -sf /opt/projt-portal/bin/projecttick-ctl /usr/bin/projecttick-ctl
   chmod +x /usr/bin/projecttick-ctl
fi

%preun
if [ $1 -eq 0 ]; then
   # Called upon real uninstallation (not upgrade)
   projecttick-ctl stop
   rm -f /usr/bin/projecttick-ctl
fi

%files
%defattr(-,root,root,-)
/opt/projt-portal

%changelog
* Sun Feb 22 2026 Project Tick Admin <admin@projecttick.local> - 1.0.1-1
- Initial RPM Package configuration for AMD64 pt-rhel-runner-01
