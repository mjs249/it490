#!/bin/bash



echo "Stopping ZeroTier service..."

sudo systemctl stop zerotier-one



echo "Deleting identity files..."

sudo rm -f /var/lib/zerotier-one/identity.public /var/lib/zerotier-one/identity.secret



echo "Restarting ZeroTier service to generate a new identity..."

sudo systemctl start zerotier-one



sleep 2



read -p "Enter the network ID to join: " networkid

sudo zerotier-cli join "$networkid"



echo "Process complete. Authorize the new identity on your network."
