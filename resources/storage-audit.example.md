# Storage Audit Example

## Slot-numbering convention

Example front bays use sequential local slot numbers. Replace this text in your
private audit with your own verified convention.

## Storage inventory

| Slot | gptid | daX | Serial | Model | Capacity | Type | Pool/Vdev | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | example-gptid-001 | da0 | SERIAL-EXAMPLE-001 | Example SAS Disk | 4TB | SAS | example-pool | |
| 2 | example-gptid-002 | da1 | SERIAL-EXAMPLE-002 | Example SATA Disk | 8TB | SATA | example-pool | FAULTED |

## USB devices

| Location | gptid | daX | Serial | Model | Capacity | Type | Pool | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Spare | example-gptid-usb-001 | da2 | SERIAL-EXAMPLE-USB-001 | Example USB Disk | 32GB | USB | example-boot-pool | WARM SPARE |

## Post-reboot notes

Add local audit procedures to the ignored private copy.
