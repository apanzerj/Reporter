Introduction
============

One of the things often requested by Zendesk customers is a view that shows all satisfaction and/or a way to export the satisfaction ratings and comments. While this isn’t something built into Zendesk, there is a way to do this using the API. 

Below is a script that does the following:

1.) If you have a “default view” noted in the script, it will use that view unless you have specified a different view via the command line.

2.) The script will call the API and pull down all the tickets from that view including related requesters and organizations. 

3.) Once it has pulled all this information it will export, to a CSV, all this information.  

Notable Features
================

The script uses the API to pull in the related information. This is, at the moment, undocumented feature is immensely valuable. We only include the bare minimum in this call, however, by only returning id, name, and url (of the resource). 

Stumbling Blocks
================

There were a few stumbling blocks on this script. First off, I hadn’t known at the time that I could pull in related organizations and users in one call. So the initial script was written to save bandwidth and store the users and orgs in an array as each were fetched. At first run, this meant that each row of the spreadsheet was a product of 3 different API calls (lookup requester, lookup organization, lookup assignee). 

Each time I converted an id (such as requester_id) to a name (such as Adam Panzer) I saved the result in an associative array that was suitably named. On the next row I would check to see if I had already looked up the organization_id, requester_id, or assignee_id. 

Since the API has the ability to save me from doing this I just populated the necessary arrays with data from the same call and used the existing code (with API calls) to be a fallback if for some reason the first call does not include information I need. 

Dealing with time, and time zones was also difficult. Zendesk returns all times with a slightly standard timestamp. The timestamp is like this (as an example) 2012-10-15T08:57:09Z. This is actually a format PHP recognizes except for the Z at the end.  The Z is actually an indicator of the time zone (Zulu Time: http://en.wikipedia.org/wiki/Zulu_time#Time_zones). Unfortunately PHP doesn’t like the Z so we have to cut it out and then convert the string to the PST time zone (where Zendesk is located). 

Notes
=====

No two computers are the same. This post has been tested on, and written, using a Mac. Your system and experience may be different and there are several things to be aware of when using this code:

1.) This code requires a semi-up-to-date version of PHP. If you are using an older version you may run into issue with json_encode/json_decode. 

2.) curl_wrap is not a perfect function. It is meant to be a wrapper for the actual php curl library. That being said, you should make sure you know how to pull data from the API via curl on the command line before working on this script or working PHP. In addition, before starting any project with an API, make sure you can pull the information, in the raw, using only curl, first. Then try and write code around that information afterward. 

3.) NULL. Many customers have received this from their installations and it can be due to a few possible problems. One of them is that some installations of curl use outdated certificate bundles. 

Paraphrased (from forum comment @ Zendesk):
The sample script that is posted points to https:// and most people have curl installs that have outdated ways of handling SSL.. If you're getting null, you have to get a pem script and set the option in curl to use it.. Please click the link for the correct answer to this problem..
http://stackoverflow.com/a/316732


Disclaimer
==========

This code is provided as-is and comes with no guarantees, support, or other type of warranty against failure. By using this you agree to these terms and release Zendesk from any and all liability and/or liability due to loss of data. 

If you do not agree to these conditions please do not use the code. The goals of this post is to help you find new and interesting ways of accessing the API. 

Getting Help (part of the disclaimer)
=====================================

In order to provide help to our customers we offer a wide range of support services. In regards to getting support with this specific code I am going to ask that you use our GitHub repository which has an issues system built into it. You can create a new issue by visiting the repository for this project https://github.com/apanzerj/Reporter and click on Issues. Then click “New Issue”.

The reason for this is because, as stated in the disclaimer, there is no support for this code internally here at Zendesk. While I still want to help customers with issues we have to keep support requests external to Zendesk.com so we can still share these projects without overloading our support. 

Code for this project is being kept at this repository: https://github.com/apanzerj/Reporter

If you want to download the code without using git hub you can click the link that says Zip to get a zip file of the project. 

If you want to use git hub please fork the project. 