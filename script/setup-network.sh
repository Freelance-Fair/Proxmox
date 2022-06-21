#!/bin/bash
if [[ "$#" -ne 2 ]]; then
        echo "Invalid argument count"
        exit 1
fi

hostnamectl set-hostname $2
echo "$1 $2" >> /etc/hosts

ifdown ens18

sed -i -E "s/address\s+[0-9\.]+/address $1/" /etc/network/interfaces

ifup ens18
