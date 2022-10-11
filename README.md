# inginf_bot
A Telegram bot template to share university groups.

## Instructions: branching, naming convention, etc.
The branch *master* contains the old code (the one that runs on the server right now). The branch *dev/base* contains the old development branch, that is a good place to start. We can choose it as a true base or discard it.

About naming convention, use dev/<branch_name> for new branches. Once you have cloned this repository, checkout to dev/base (or the branch we chose as a base) and then create a new branch from it, using git switch or git checkout -b. You can't directly push to master or dev/base, so you can't destroy anything here.
Be sure to not publish any token or user ID in this repository, since it could be potentially seen from other malicious users.
