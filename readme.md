# Proxmox connector
## How to run
`$ php vendor/bin/manage --{arguments}

## Required arguments
- --action
- --vm

## Optional arguments
- --command `what to execute in the "execute" action`
- --no-wait `should script wait during "execute" action`

## Settings
Settings can be configured by either command arguments or environment variables:
- `--endpoint` or `PROXMOX_ENDPOINT`
- `--token` or `PROXMOX_TOKEN`

The following settings are only needed a `build` action
- `--target-node` or `PROXMOX_TARGET_NODE`
- `--template` or `PROXMOX_TEMPLATE`
- `--name-prefix` or `PROXMOX_NAME_PREFIX`
- `--host-template` or `PROXMOX_HOST_TEMPLATE`
- `--network` or `PROXMOX_NETWORK`
- `--ip-pool` or `PROXMOX_IP_POOL`

For the `generate-upstream` or `deploy-upstream` action:
- `--upstream` or `PROXMOX_UPSTREAM`