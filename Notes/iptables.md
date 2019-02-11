# IPTables CMD Tutorials
## START iptables
`service iptables start`

On debian install iptables-persistent
The package will automatically load `/etc/iptables/rules` for you during boot
Any time you modify your rules, run `/sbin/iptables-save > /etc/iptables/rules` to save them. You can also add that to the shutdown sequence if you like.

# Restore rules
to restor rules just do `/sbin/iptables-restore < /etc/iptables/rules`

# To restore rules after restart
create new file /etc/network/if-pre-up.d/iptables with the following content:

```bash
#!/bin/sh
/sbin/iptables-restore < /etc/iptables/rules
```
make this file executable
```bash
chmod +x /etc/network/if-pre-up.d/iptables
```
On this point you should be ready with your iptables set-up.


```bash
iptables -L
iptables -A INPUT -s 8.8.8.8 -j ACCEPT
iptables -A INPUT -s 8.8.4.4 -j ACCEPT
iptables -A INPUT -s x.x.x.x -j ACCEPT
iptables -A INPUT -s x.x.x.x -j ACCEPT
iptables -A INPUT -s x.x.x.0/24 -j ACCEPT
iptables -A INPUT -s 127.0.0.1 -j ACCEPT
iptables -A INPUT -s 0.0.0.0/0 -j REJECT
iptables -A OUTPUT -s 0.0.0.0/0 -j ACCEPT
iptables -A FORWARD -s 0.0.0.0/0 -j ACCEPT

/usr/local/freeswitch/bin/fs_cli 
```