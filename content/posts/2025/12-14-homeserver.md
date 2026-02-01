--- 
title: Home Server
categories: [home]
date: 2025-12-14
toc: true
image: /images/2025-12-14/gigabyte.png
draft: false
fediverse: 115719389120225061
---

_This article describes roughly how I configured a Mini PC as a NixOS
homeserver hosting [Pihole](https://pi-hole.net/), [Syncthing](https://syncthing.net/), [Jellyfin](https://jellyfin.org/), [Home Assistant](https://www.home-assistant.io/) and [Music
Assistant](https://www.music-assistant.io/). It is not a tutorial but may
provide some useful snippets._

## History

I've been running a mess of services within my home network:

- **Rasberry Pi 4**:
  - Pihole (ad blocker and local DNS)
  - MPD (Music Player Daemon)
  - Jellyfin (Media Server)
  - Syncthing (File synchronization service)
  - Music Assistant (another music server)
- **Rasberry Pi 5**:
  - Home assistant (Controls and monitors the flat)
  - Faster Whisper (Open AI's voice recognition service, used by HA)
  - Piper (text to voice service)
- **Synology Diskstation (NAS)**: 
  - Syncthing (the "master" synchronization service)
  - Linkling (a web bookmark manager)

![current "rack"](/images/2025-12-14/rack.png)
*My sophisticated stack*

In addition some notable pieces of hardware:

- [Unifi Express](https://techspecs.ui.com/unifi/cloud-gateways/ux?subcategory=all-cloud-gateways) (smart router which also provides an internet facing VPN)
- [Nabu Casa Voice Assistant](https://www.home-assistant.io/voice-pe/) (voice control client for home assistant)

The first-world problems are:

- My **voice assistant** is very dumb and the Rasberry Pi5 can only run the least
  intensive model.
- The Pi 4 can stream videos just fine, but it's not powerful enough to
  **transcode subtitles** (?).
- I can't **stream music** to different rooms in the flat.
- There's **no reverse proxy** so I access all the services via. their ports (e.g.
  `ha.home:8123`).
- Although the NAS can run **containers**, it's not significantly more capable (if
  at all) than the Rasberry PIs.

And finally, and worst of all, **I have no idea how any of it works**:

- There may or may not be configuration files that I edtied.
- I don't know which services run on which devices. 
- Some of the services need to be started _manually_ if there's a powercut.

So I've decided to iterate on the whole thing and install **everything with
NixOS in a Mini PC**. I'll probably then repurpose one or both of the Pis to
act as streaming clients for dumb speakers[^dumb].

## Mini PC

My Rasberry PI 4 has been running silently and almost continuously for
maybe **six years** and it's
been perfect for MPD and Pihole, but Jellyfin and Music Assistant push it over
the edge.

I considered splurging on a high-power Mini PC, but that would mean that it
would be capable of playing games and maybe even running a local LLM, but as
I don't have a gaming device there's a high chance I'd start playing games no
my "home server" which probably isn't a good idea.

```text
$ neofetch
daniel@gigabyte
---------------
OS: NixOS 25.11.20251130.d542db7 (Xantusia) x86_64
Host: GIGABYTE MCMLUEB-00
Kernel: 6.12.59
Uptime: 42 mins
Packages: 1025 (nix-system), 1848 (nix-user)
Shell: zsh 5.9
Resolution: 3840x2160
DE: sway
WM: Mutter
WM Theme: Adwaita
Terminal: /dev/pts/0
CPU: Intel i5-10210U (8) @ 4.200GHz
GPU: Intel CometLake-U GT2 [UHD Graphics]
Memory: 4086MiB / 31936MiB
```

Deciding to go with something more modest I visited my local [CEX](https://en.wikipedia.org/wiki/CeX_(retailer)) store (second
hand devices) and was initially going to purchase an Intel NUC device until I
noticed that it didn't have a **headphone/mic** jack, so I purchased a cheaper one
for £180 that did, a Gigabit i5. It came with 8 gigabytes of RAM but I happened to have
a couple of 16GB sticks of DDR4 RAM lying about:

![current "rack"](/images/2025-12-14/gigabyte.png)
*The Gigabyte Mini PC with audio out*

## Operating System

NixOS is an operating system that is built from declarative configuration.
There are no `apt-get install package1 package2`, no `vim
.config/mpd/server.conf`, no blind fucking around. One edits the **Nix**
configuration rebuilds the system and then put the configuration files in
version control. Which is good, because **I have a lot of custom
configuration**. 

{{< callout >}}
This is far more than "dotfile" management. The entire state of the system is
represented in configuration and after applying a configuration you're able to
boot into any previously applied configuration.
{{</ callout >}}

I use a single repository to manage the configuration for all of my
laptops and it typically takes about an hour to "onboard" a new laptop after
which the system is essentially indistinguishable from the other laptops.

## Installation

I'm **sure** there's a better way to do this. But my current installation
method is as follows:

- Use the NixOS installer on a USB stick.
- Install with desktop environment.
- Reboot into the new system.
- Edit `/etc/configuration.nix` with `nano` (😧)
- Change the hostname setting (to `gigabyte` in this case).
- Add the `git`, `vim` and `openssh` packages (the default list includes only
  `firefox`).
- Run `nixos-rebuild switch`
- Reboot
- `git clone git@github.com:dantleech/mynixossystem` (the private repository
  containing my configurations).
- Create a new folder for the host (I have one directory per host).
- Copy the `/etc/nixos/configuration.nix` and
  `/etc/nixos/hardware-configuration.nix` to the new folder.
- Update the `flake.nix` to reference the new host...
- Rebuild the system using the **flake** `nixos-rebuild switch --flake '.#gigabyte'`.

Normally my laptops have the same configuration but the server is not a
laptop. So I started a new configuration and pulled in anything I needed from
the shared configuration - Tmux, Zsh, Git and my entire Neovim configuration
and all the language servers.

At this point I was able to disconnect the keyboard and move the Mini PC to
the corner of the room and continue to configure it headlessly over SSH.

{{< callout "info" >}}
Although some things work first time, watching the logs with `jounalctl -f`
was very helpful today.
{{</ callout >}}


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
    80 443
    8384 22000
  ];
  networking.firewall.allowedUDPPorts = [
    22000 21027
  ];
}
```

## Reverse Proxy

As the Mini PC will be hosting multiple web applications each will need a
dedicated port, to access them we have two options:

- Open up the ports (i.e. blast holes in the [firewall](https://nixos.wiki/wiki/Firewall)).
- Use a reverse proxy (i.e. listen on port 80 or 443 and forward traffic to
  backend services using the `Host` header).

Opening up the ports is easy, and we've already done that for Syncthing above,
but given that we have domain names I'd rather type `pi.home` instead of
`pi.home:8015`.

Setting up a reverse proxy would normally fill me with **fear** and
**anxiety**. But with Nixos it's fucking easy:


```nix
{
  services.caddy = {
    enable = true;
    virtualHosts."pi.local".extraConfig = ''
      reverse_proxy http://localhost:8015
    '';
    virtualHosts."jellyfin.local".extraConfig = ''
      reverse_proxy http://localhost:8096
    '';
    virtualHosts."ha.local".extraConfig = ''
      reverse_proxy http://localhost:8123
    '';
    virtualHosts."music.local".extraConfig = ''
      reverse_proxy http://localhost:8095
    '';
    virtualHosts."snap.local".extraConfig = ''
      reverse_proxy http://localhost:1705
    '';
  };
}
```

{{< callout "info" >}}
Because `.local` is a [valid TLD](https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains) Caddy can generate an SSL certificate for these domains.
I don't know why I want that on a local network, but hey, it's free.
{{</ callout >}}


## Jellyfin

Jellyfin is a media server, I have a client on my "smart" TV and it's good:

```nix
{ pkgs, ... }:
{
  services.jellyfin = {
    enable = true;
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

The Pihole is ostebsibly an ad-blocking DNS server what you configure your
router to point at. When your computer requests
`https://myadcompany.com/trackme` the Pihole will simply not translate the
domain name to an IP address. This is great but as a DNS server it also means
we can register **custom DNS records for the local network** and now we can do
that in configuration.

There was no NixOS wiki page for configuring the Pihole but I was
able to piece it together from the [configuration docs](https://search.nixos.org/options?channel=unstable&query=services.pihole):

```nix
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
    lists = [
      {
        url = "https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts";
      }
    ];
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

After that I tested it by changing the DNS server in `/etc/resolv.conf` before making the
switch on my router.

## Home Assistant

Home Assistant monitors and controls smart devices in the home. I previously
had this setup on the Rasberry Pi 5.

The NixOS wiki describes three different ways of setting up home assistant, I
chose the "declarative" way. It **seems** (?) that you can't setup devices
however and I needed to add those manually[^manually].

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

You need **components** in order to integrate with devices and they are not enabled
by default. The [wiki](https://wiki.nixos.org/wiki/Home_Assistant#First_start)
isn't very clear...

It's necessary to monitor the logs and look for the `ModuleNotFoundError: No module named '<module name>'` errors and then
(😱) grep the [components-packages.nix](https://github.com/NixOS/nixpkgs/blob/master/pkgs/servers/home-assistant/component-packages.nix)
file to find out which modules to add and then add them to the list of `extraComponents` in the config:

```nix
{
  services.home-assistant = {
    # ...
    extraComponents = [
      # these components were listed in the wiki
      "analytics"
      "google_translate"
      "met"
      "radio_browser"
      "shopping_list"
      "isal"

      # these are the ones I needed to add:

      "ipp"             # internet printing protcol (discover printers)
      "improv_ble"      # for my Nabu Casa Voice Assistant
      "shelly"          # for my Shelly H&T Gen3
      "ibeacon"         # Never found out what these devices are
      "tplink"          # for TP Link smart lights and sockets
      "synology_dsm"    # to connect to the NAS
      "wyoming"         # whisper (voice to text)
      "xiaomi_ble"      # not sure!
      "music_assistant"
    ];
  };
}
```

## Voice Assistant

In order for the voice assistant to understand and respond to voice commands
I'm using _whisper_ and _piper_ respectively:

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

The `distil-medium.en` model seems to provide more accuracy than the previous
model on the Rasberry Pi 5 but I'm not sure it's faster. I also changed the
"piper" voice from "Alan" to "Southern English Female". Because quite
honestly, I'd had enough of Alan.

I then (had to?) manually add the Whisper and Piper integrations to home
assistant and configure the Voice Assistant to use them.

## Music Assistant

[Music Assistant](https://www.music-assistant.io/) is a music server that integrates with a wide-range of music
providers and a wide selection of _players_. I use it with Tidal and I _want_
to use it with my local music collection. I want to be able to stream music to
different rooms in my house. This can be done with [Snapcast](https://nixos.wiki/wiki/Snapcast).

Below I enable Music Assistant and the provider packages that I want to use:

```nix
{ pkgs, ... }:
{
  services.music-assistant = {
    enable = true;
    providers = [
      "tidal"          # tidal
      "builtin"        # don't know but sounds good
      "builtin_player" # also fine
      "hass"           # home assistant
      "snapcast"       # snapcast
    ];
  };
}
```

To playback music using the in-built audio device I neded to run a
`snapclient`. I had to add the `snapcast` package to my **main list of
packages** as it's not currently configurable via. a `service` option.
Therefore I also needed to add a systemd unit to start it automatically:

```nix
{
  systemd.user.services.snapclient-local = {
    wantedBy = [
      "pipewire.service"
    ];
    after = [
      "pipewire.service"
    ];
    serviceConfig = {
      ExecStart = "${pkgs.snapcast}/bin/snapclient";
    };
  };
}
```

I was then also able to manually add the Music Assistant integration to home
assistant and add a [voice assistant
blueprint](https://github.com/music-assistant/voice-support?tab=readme-ov-file)
to support "Play the artist Pink Floyd" commands.

![home assistant integration](/images/2025-12-14/pinkfloyd.png)
*The Music Assistant Integration in Home Assistant*

## That's it!

I've finised for today and I'll wrap up this post. I still need to add a few
services but this was the worst of it. **MY RASBERRY PIS ARE FREE**.

---

[^dumb]: I was going to make an MPD based setup as detailed
    [here](http://www.hietala.org/multi-room-audio-with-mpd-and-snapcast.html)
    but now I'll maybe use Music Assistant - in anycase Snapcast will be used
    as detailed in this post.

[^manually]: adding devices in HomeAssistant manually made me sad. Having an
    index of which devices are assigned to which IP address would already be
    useful. Maybe it's possible? Send me a letter and let me know.
