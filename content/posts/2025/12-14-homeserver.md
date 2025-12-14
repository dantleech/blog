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

It initially came with 8GB of ram, and I noticed that Jellyfin and Whisper
were taking abut half of that between them. Fortunately I had two 16GB sticks
of DDR4 RAM left since I upgraded my laptop.

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

## Debugging

Sometimes things don't work and you need to look at the logs:

```
jounalctl -f
```

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

## Pihole

The Pihole is ostebsibly an ad-blocking DNS server. When your computer
requests `https://myadcompany.com/trackme` the Pihole will simply not
translate the domain name to an IP address. This is great but as a DNS server
it also means we can register **custom DNS records for the local network** and
now we can do that in configuration.

Unfotunately there was no NixOS wiki page for configuring the Pihole but I was
able to piece it together:

```
{  
  # the web interface (using port 8015 as we'll be putting it behind a reverse
  # proxy later)
  services.pihole-web = {
    enable = true;
    ports = [
      "8015"
    ];
  };

  # the DNS service
  services.pihole-ftl = {
    enable = true;
    openFirewallDNS = true;
    openFirewallDHCP = false;
    useDnsmasqConfig = true;
    settings = {
      dns = {
        upstreams = [
          # google
          "8.8.8.8"
          "8.8.4.4"

          # opendns
          "208.67.222.222"
          "208.67.220.220"
        ];
        hosts = [
          "192.168.1.17 pi.hole"
          "192.168.1.126 laptop1.home"
          "192.168.1.112 ha.home"
          "192.168.1.140 nas.home"
          "192.168.1.6 x230.home"
          "192.168.1.222 x5.home"
          "192.168.1.17 gigabyte.home"
          # etc
        ];
      };
    };
  };
}
```

## Reverse Proxy

As the Mini PC will be hosting multiple web applications each will need a
dedicated port, to access them we have two options:

- Open up the ports.
- Use a reverse proxy.

Opening up the ports is easy, and we've already done that for Syncthing above,
but given that we have domain names I'd rather type `pi.home` instead of
`pi.home:8015`.

Setting up a reverse proxy would normally fill me with **fear** and
**anxiety**. But with Nixos it's fucking easy:


```nix
{
  services.caddy = {
    enable = true;
    virtualHosts."pi.home".extraConfig = ''
      reverse_proxy http://localhost:8015
    '';
    virtualHosts."jellyfin.home".extraConfig = ''
      reverse_proxy http://localhost:8096
    '';
  };
}
```

> [^NOTE]
> Because `.home` is a valid TLD Caddy can generate an SSL certificate for these domains.
> I don't know why I want that, but hey, it's free.

## Home Assistant

Home Assistant monitors and controls smart devices in the home. I previously
had this setup on the Rasberry Pi 5.

The NixOS wiki describes three different ways of setting up home assistant, I
chose the "declarative" way. It **seems** (?) that you can't setup devices
however and I needed to add those manually.

I had to configure the `x_forwarded_for` setting to allow it to work behind
the reverse proxy:


```nix
{  
  services.home-assistant = {
    enable = true;
    config = {
      http = {
        trusted_proxies = [ "::1" ];
        use_x_forwarded_for = true;
      };
    };
}
```

You need modules in order to integrate with devices and they are not enabled
by default. The [wiki](https://wiki.nixos.org/wiki/Home_Assistant#First_start)
isn't very clear on this and it took me a while to figure out how to enable
the modules.

It's necessary to monitor the logs and look for the `ModuleNotFoundError: No module named '<module name'` errors and then
grep [components-packages.nix](https://github.com/NixOS/nixpkgs/blob/master/pkgs/servers/home-assistant/component-packages.nix)
to find out which modules to add and then (and this is the missing
informatino) add them as `extraComponents` in the config:

```nix
{
    extraComponents = [
      # these components were listed in the wiki
      "analytics"
      "google_translate"
      "met"
      "radio_browser"
      "shopping_list"
      "isal"

      # these are the ones I needed to add:

      "ipp"          # internet printing protcol (discover printers)
      "improv_ble"   # for my Nabu Casa Voice Assistant
      "shelly"       # for my Shelly H&T Gen3
      "ibeacon"      # Never found out what these devices are
      "tplink"       # for TP Link smart lights and sockets
      "synology_dsm" # to connect to the NAS
      "wyoming"      # whisper (voice to text)
      "xiaomi_ble"   # not sure!
    ];
  };
}
```

## Voice Assistant

In order for the voice assistant to understand and respond to voice commands
I'm using _whisper_ and _piper_:

```nix
{
  services.wyoming.faster-whisper.servers.assist = {
    enable = true;

    model = "distil-medium.en";
    uri = "tcp://0.0.0.0:10300";
    language = "en";
  };
  services.wyoming.piper.servers.assist = {
    enable = true;
    uri = "tcp://0.0.0.0:10200";
    voice = "en_GB-southern_english_female-low";
  };
}
```

