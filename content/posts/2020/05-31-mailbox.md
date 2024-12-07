--- 
title: Mailbox dot org
categories: [email]
date: 2020-05-31
image: images/2020-05-31/userinterface.png
aliases:
  - /blog/2020/05/31/mailbox
---

I had been hosting my own email for several years. I started using Gmail in
2005 or so but the advertising really bugged me, so I thought I would host my own
email - it also seemed like a cool project. But I learned that it's not that
easy.

Later I heard about [sovereign](https://github.com/sovereign/sovereign) which
is an Ansible playbook to setup your own cloud. All you need to do is buy a
dedicated server (I used a [Scaleway
Dedibox](https://www.scaleway.com/en/dedibox/start/start-2-s-sata)) and run
Ansible to install and configure the software.

Sovreign sets up lots of services on your server - but in regards to email is
installs Dovecot/Postfix server with storage on an encrypted mount, it also
provided a [roundcube](https://roundcube.net/) web interface.

I never succeeded in getting mail filtering to work properly. I rarely
received SPAM but I did receive unwanted notifications from my server which I
deleted daily (!).

Upgrading Sovereign was a pain - as you basically fork the project in order to
customize it, you would have to merge changes back in.

The truth is that you still need to know how all that software works in order
to manage it - and as I am somebody who would spend several hours a year
battling with this software - I didn't understand it.

So I decided to look elsewhere.

Mailbox dot org
---------------

After an in-exhaustive search for mail hosting options - and after seeing some
recommendations on Twitter, I decided to try
[mailbox](https://mailbox.org/en/).

Probably the most important factor when considering mail hosting is privacy
and security. 

The company is based in Berlin and has a long
[history](https://mailbox.org/en/company).

I gather that Germany has strong privacy laws, but not the strongest.  Mailbox
provide a transparent [security
report](https://mailbox.org/en/company#transparency-report) showing the number
of information requests made by various entities.

Cost
----

The initial account is free and a full account is €12 a year, but it's also
flexible and you can tune your storage requirements and price to your tastes.

![customize](/images/2020-05-31/mailbox-cost.png)

You can make payments when ever you like and add credit to your account
through the web interface.

Migration
---------

**TL;DR;** migrate mail in the web interface.

I needed to transfer around 1.2GB of mail from my mail server to mailbox.org.

My first approach was to use `offlineimap` to achieve this.
[Offlineimap](https://www.offlineimap.org/) is normally used to sync data
_from_ a mail server to your `localhost`. But by switching somethings around
you can [restore mail to a
server](http://www.offlineimap.org/doc/backups-restore.html).

This approach was going well until I noticed that all the mail in my
mailbox.org account had a delivery date of _today_.

This issue was [reported
here](https://userforum-en.mailbox.org/topic/wrong-date-of-reception-of-mails-after-imap-migration)
and the advice was to not import via. IMAP.

Instead you should add your other account to the Mailbox account and "drag and
drop" the mail from one account to another.

Unfortunately I had gotten excited and had removed Dovecot from my web server,
fortunately it re-installed without an issue and I was able to attach my
legacy account and transfer all the mail correctly.

Services
--------

The mailbox account provides mail, but it also provides some cloud storage
(100MB), Calendar, an office suite and various other things.

I was initially a bit put-off by all the extras, but I discovered that you can
disable them, and perhaps I may find some of them useful some day.

The cloud storage doesn't provide much capacity by default and there is no
option to sync data (you can mount it as a WebDAV drive though). For storage I
will continue to use [Syncthing](https://syncthing.net/) on my web server (and
laptops).

As a side-note, Syncthing is a decentralised solution (it will sync between
two computers on a local network) and I can take advantage of my 1TB of
storage I have on my ~€10 a month server).

User Interface
--------------

I've found the user interface to be very good. You have a range of "views" for
email: vertical (as above), list (gmail-like), horizontal and compact.

![mail view](/images/2020-05-31/userinterface.png)

Double clicking on a mail opens a modal. All the modals can be managed like
windows - you can drag, resize, dock and close them.

The configuration can be a bit overwhelming - and I had too google in order to
find where the mail filters are configured. But there is lots to discover. You
even have access to an error log.

Security
--------

Mailbox supports 2 factor authentication, and provides the option to easily
encrypt / sign your emails from the web interface.

Own Domain
----------

Mailbox happily permits you to use your own domain and add email aliases.

I had to modify the DNS record of my domain as
[documented](https://kb.mailbox.org/display/MBOKBEN/Using+e-mail+addresses+of+your+domain).

You can then use your domain as the primary email for your account (so I login
to mailbox using my email address, rather than the default `user@mailbox.org`
address).

Consumption
-----------

Nothing changed in the way I consume my mail outside of the web interface. I
am using [K9Mail](https://f-droid.org/en/packages/com.fsck.k9/) (installed via
Fdroid) on my Android phone, and I just reconfigured the Imap / SMTP settings.

Equally I can setup `offlineimap` to sync emails to my localhost and use
[Neomutt](https://neomutt.org/) as a mail client.

Summary
-------

So far very happy with it and am very glad not to manage my own mail
server.
