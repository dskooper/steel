# <img height=50px src="https://static.wikia.nocookie.net/minecraft_gamepedia/images/6/64/Iron_Ingot_JE1.png/revision/latest?cb=20201014002107"></img> Steel

Steel is a lightweight (and almost feature-complete) web frontend for Twitter's 1.0 and 1.1 REST APIs.

## Usage:
To use steel, you must provide your own compatible Twitter v1.0/v1.1 REST API in the form `http(s)://insert-api-here.com`
  - **Do not include a "/" at the end** as this client does not account for that :(

### List of available APIs
This is a list of active APIs I know of (reminder that Steel works with **any** accurate implementation)
- [BlueTweety](https://twb.preloading.dev)
- [DigUpTheBird](https://tun.3a33.zip) (not 24/7)

### Why use Steel?
- Steel, unlike frontends such as Nitter, allows you to login to your account and send/receive tweets.
- Steel, unlike **every other Twitter frontend**, allows the client to specify their own API!
  - This mmeans that instead of being tailored to one singular client, it instead suppports any (mostly-complete) implementation of the Twitter API.

## Features implemented:
- Home timeline and mentions
- Create and reply to tweets with images
- Search for and follow/unfollow users
- Send and receive direct messages
- Manage your profile and account settings

## TODO (in no specific order):
- Trending topics (I will likely force this to be worldwide: `GET trends/place.json?id=1`)
- A better UI that isn't just one color
- Search through tweets for a specific term or hashtag
