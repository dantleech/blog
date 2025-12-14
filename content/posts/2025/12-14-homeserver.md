--- 
title: Home Server
categories: [programming,php]
date: 2025-12-14
toc: false
#image: /images/2024-12-28/cdto.png
draft: true
---

I've been running a mess of services within my home network:

- Rasberry Pi 4:
  - Pihole (ad blocker and local DNS)
  - MPD (Music Player Daemon)
  - Jellyfin (Media Server)
  - Syncthing (File synchronization service)
- Rasberry Pi 5:
  - Home assistant (Controls and monitors the flat)
  - Faster Whisper (Open AI's voice recognition service, used by HA)
  - Piper (text to voice service)
- Synology Diskstation (NAS): 
  - Syncthing (the "master" synchronization service)
  - Linkling (a web bookmark manager)

In addition some notable pieces of hardware:

- Unifi Express (smart router which also provides an internet facing VPN)
- Nabu Casa Voice Assistant (voice control for home assistant)

The pihole provides local DNS:

```
192.168.1.117 pi4.hole
192.168.1.1 gateway.home
192.168.1.112 pi5.home
192.168.1.112 ha.home
192.168.1.140 nas.home
192.168.1.140 links.home
192.168.1.140 jellyfin.home
```

The problems are:

- My **voice assistant** is very dumb asd the Rasberry Pi5 can only run the least
  intensive model.
- The Pi 4 can stream videos just fine, but it's not powerful enough to
  **transcode subtitles** (?).
- I can't **stream music** to different rooms in the flat.
- There's **no reverse proxy** so I access all the services via. their ports (e.g.
  `ha.home:8123`
- Although the NAS can run containers, it's not significantly more capable (if
  at all) than the Rasberry PIs.

And finally, and worst of all, **I have no idea how any of it works**. I've
edited and created configuration files which I've since forgotten about and
when there's a power cut I need to manually remember how to restart some
services (were they dockerised? running in a tmux session? do they need to be
kicked?).

So I've decided to iterate on the whole thing and install **everything with
NixOS in a Mini PC**. I'll probably then repurpose one or both of the Pis to
act as streaming clients for dumb speakers.

## Mini PC

It's clear that the Rasberry PIs are not cutting the mustard properly. But it
must be said that the Rasberry PI 4 has been running almost continuously for
maybe **six years** (I think I upgraded the O/S twice in that time).

The **silence** and low power consumption of these devices is important to me,
but while the Pi 4 works perfectly for running Pihole and MPD it is not a 
solution for running Jellyfin, so I needed something more - and if I'm getting
something more then it may as well run **all the things**.

I considered splurging on a high-power Mini PC, but that would mean that it
would be capable of playing games and maybe even running a local LLM, but as
I don't have a gaming device there's a high chance I'd start playing games no
my "home server" which probably isn't a good idea.

Deciding to go with something more modest I visited my local CEX store (second
hand devices) and was initially going to purchase an Intel NUC device until I
noticed that it didn't have a headphone/mic jack, so I purchased a cheaper one
that did, a Gigabit i5.

## Operating System

NixOS is an operating system that is built 100% declarative configuration.
There are no `apt-get install package1 package2`, no `vim
.config/mpd/server.conf`, no blind fucking around. You edit configuration
files and you rebuild the system and then you put the configuration files in
version control. Which is good, because over the past 20 years I have a lot of
custom configuration and it used to take months of progressive tinkering for a
new laptop to be "restored" to the state I'd want it to be in. I now use a
single repository to manage the configuration for all of my laptops and it
typically takes about an hour to "onboard" a new laptop after which the system
is essentially indistinguishable from the other laptops.

## Installation

I'm **sure** there's a better way to do this. But my current installation
method is as follows:

- Use the NixOS installer on a USB stick.
- Install with desktop environment.
- Reboot into the new system.
- Edit `/etc/configuration.nix` with `nano` (😧)
- Change the hostname setting.
- Add the `git`, `vim` and `openssh` packages (the default list includes only
  `firefox`).
- Run `nixos-rebuild switch`
- Reboot
- `git clone git@github.com:dantleech/mynixossystem` (the repository
  containing my configurations).
- Create a new folder for the host (I have one directory per host).
- Copy the `/etc/nixos/configuration.nix` and
  `/etc/nixos/hardware-configuration.nix` to the new folder.
- Update the `flake.nix` to reference the new host...
- Rebuild the system using the **flake** `nixos-rebuild switch --flake '.#gigabyte'`.

Now, normally my laptops have the same configuration and I'd reuse shared
settings between them, but the server is obviously not a laptop, but I still
wanted to have the terminal environemnt the same. Fortunately it's easy to
reuse configurations and I was able to easily include my Tmux, Zsh and Git
setting in addition to my entire Neovim confguration with all the language
servers.

At this point I was able to disconnect the keyboard and move the Mini PC to
the corner of the room and continue to configure it headlessly over SSH.

## Disable Sleeping

I noticed that the PC kept going into standby mode, after some googling I
found the following snippet to prevent it from going to sleep:

```nix
{
  systemd.sleep.extraConfig = ''
    AllowSuspend=no
    AllowHibernation=no
    AllowHybridSleep=no
    AllowSuspendThenHibernate=no
  '';
}
```

## Mounting folders from the NAS

I have lots of media and photos on the NAS:

```nix
{
  fileSystems."/mnt/media" = {
    device = "nas.home:/volume1/Media";
    options = [ "x-systemd.automount" "noauto" ];
    fsType = "nfs";
  };
}
```

I then need to grant permission on the NAS to my `gigabyte` host and rebuild
and it's done.

## Syncthing

Although I _could_ mount the music folder from the NAS, for whatever reason I
prefer to sync my collection to the local drive. I could say it adds some
geo-redundancy across the 5 meters separating the two devices, but it's more
that the Syncthing folders are not shared on NFS but they _are_ backed up
(unlike my collection of videos).

The NixOS wiki was useful in providing the following snippet.

```nix
{
  services = {
    syncthing = {
      enable = true;
      group = "users";
      user = "daniel";
      guiAddress = "0.0.0.0:8384";
      overrideDevices = true;
      overrideFolders = true;
      dataDir = "/home/daniel/Documents"; # Default folder for new synced folders
      configDir = "/home/daniel/.config/syncthing";
      settings = {
        devices = {
          "nas" = {
            id = "XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX";
          };
        };
      };
    };
  };
}
```

By default NixOS has a sensible firewall policy in that only ports 80 and 443
are open. I had to open the Syncthing ports:

```nix
{
  networking.firewall.allowedTCPPorts = [
    8384 22000
  ];
  networking.firewall.allowedUDPPorts = [
    22000 21027
  ];
}
```

## Jellyfin

Again the Nixos wiki helped:

```nix
{ pkgs, ... }:
{
  services.jellyfin = {
    enable = true;
    openFirewall = true;
    user = "daniel";
  };
  environment.systemPackages = [
    pkgs.jellyfin
    pkgs.jellyfin-web
    pkgs.jellyfin-ffmpeg
  ];
}
```











