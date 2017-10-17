===================================
Contributing to Subrion CMS Project
===================================

About
=====

This document provides a set of best practices for Subrion CMS contributions -
bug reports, code submissions / pull requests, etc.


Submitting bugs
===============

Due diligence
-------------

Before submitting a bug, please do the following:

* Perform **basic troubleshooting** steps:

    * **Make sure you're on the latest version.** If you're not on the most
      recent version, your problem may have been solved already! Upgrading is
      always the best first step.
    * **Try older versions.** If you're already *on* the latest release, try
      rolling back a few minor versions (e.g. if on 4.0.x, try 3.3.x) and see
      if the problem goes away. This will help the devs narrow down when
      the problem first arose in the commit log.

* **Search the project's bug/issue tracker** to make sure it's not a known
  issue.
* If you don't find a pre-existing issue, consider **checking with the user
  forums** in case the problem is non-bug-related.

What to put in your bug report
------------------------------

Make sure your report gets the attention it deserves: bug reports with missing
information may be ignored or punted back to you, delaying a fix.  The below
constitutes a bare minimum; more info is almost always better:

* **What version of PHP/MySQL/Apache are you using?**
* **Which version or versions of the software are you using?** Ideally, you
  followed the advice above and have ruled out (or verified that the problem
  exists in) a few different versions.
* **How can the developers recreate the bug on their end?** If possible,
  include a copy of your code, or reproduce the steps in details, etc.

    * A common tactic is to pare down your code until a simple (but still
      bug-causing) "base case" remains. Not only can this help you identify
      problems which aren't real bugs, but it means the developer can get to
      fixing the bug faster.


Contributing changes
====================

Version control branching
-------------------------

* Use ``develop`` branch for development, don't use ``master`` branch. We reserve
  master branch for clear releases & tags.
* Always **make a new branch** for your work, no matter how small. This makes
  it easy for others to take just that one set of changes from your repository,
  in case you have multiple unrelated changes floating around.

    * A corollary: **don't submit unrelated changes in the same branch/pull
      request**! The maintainer shouldn't have to reject your awesome bugfix
      because the feature you put in with it needs more review.

* **Base your new branch off of the appropriate branch** on the main
  repository:

    * **Bug fixes** should be based on the branch named after the **oldest
      supported release line** the bug affects.

        * E.g. if a feature was introduced in 3.3, the latest release line is
          4.0, and a bug is found in that feature - make your branch based on
          3.3.  The maintainer will then forward-port it to 4.0 and master.
        * Bug fixes requiring large changes to the code or which have a chance
          of being otherwise disruptive, may need to base off of **develop**
          instead. This is a judgement call -- ask the devs!

    * **New features** should branch off of **the 'master' branch**.

        * Note that depending on how long it takes for the dev team to merge
          your patch, the copy of ``develop`` you worked off of may get out of
          date! If you find yourself 'bumping' a pull request that's been
          sidelined for a while, **make sure you rebase or merge to latest
          master** to ensure a speedier resolution.

Code formatting
---------------

* **Follow the style you see used in the primary repository**! Consistency with
  the rest of the project always trumps other considerations. It doesn't matter
  if you have your own style or if the rest of the code breaks with the greater
  community - just follow along.