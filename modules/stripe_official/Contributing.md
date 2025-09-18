Contributing to Stripe Connectors Project
===

This document describes the process of contributing to Stripe Connectors project code base as well as the standards that will be applied when evaluating contributions.

Please be aware that additional agreements will be necessary before we can accept changes from contributors.

Summary
---

We are using the [NASA OpenMCT](https://github.com/nasa/openmct/blob/master/CONTRIBUTING.md) project's contribution guidline as an example.

Contribution process
---

References to roles are made throughout this document. These are not intended
to reflect titles or long-term job assignments; rather, these are used as
descriptors to refer to members of the development team performing tasks in
the check-in process. These roles are:

* _Author_: The individual who has made changes to files in the software
  repository, and wishes to check these in.
* _Reviewer_: The individual who reviews changes to files before they are
  checked in.
* _Integrator_: The individual who performs the task of merging these files.

### Branching

We follow the format of branch names:

* `feature/SRPDV001-0000/name-of-the-change` <br> Common branch naming for all the new features linked by a project management tool's ticket(Jira)
* `bugfix/SRPDV001-0000/name-of-the-fix` <br> Branch naming for bug fixes linked by a project management tool's ticket(Jira)
* `hotfix/name-of-the-fix` <br> Branch naming for prioritized changes applyed directly to live system
* `maintenance/name-of-the-maintenance-job` <br> Branch naming for a non-customer related improvement

`SRPDV001-0000` is to be changed with the ID of the project management tool's ticket(Jira).
We have a **hard cap** of branches being **no longer than 40** characters, so don't do long branch names.
As a general rule of thumb keep the `name-of-change` concise and not overly descriptive.
We do not particularly work with the branch name; our commit messages are the value holders when it comes to ticketing.

Merge requests
---

When development is complete on an issue, the first step toward merging it
is to open a Merge Request. The contributions
should meet code, test, and commit message standards as described below. Merge requests may be assigned to an _Integrator_.
Merge requests must be assigned to a _Reviewer_

Code review should take place using discussion features within the merge request.

Opened merge request should be marked by following ways:

* If the merge request is not ready for the review yet, please mark it with the label `Work in progress`
  * Additionally, you can use GitLab interface to mark the merge request as Draft.
* If the merge request is ready to be reviewed, please mark it with the label `Ready for code review`
* If you are a _Reviewer_ and you finish reviewing, including not leaving feedback, you must **Approve** the merge request by using the thumbs up
  * If the merge request is approved by at least 2 _Reviewers_ you can mark it with the label `Reviewed`
  * If the merge request has outstanding feedback you can mark it with the label `Waiting for update`
  * If the merge request has merge conflicts you can mark it with the label `Waiting for update`

Merge request author should take care of the following things:

* Author should be responsible for their merge request, making sure their merge request will not be forgotten.
* Author should react on discussion points relating to the merge request, updating the code or answering the comments from reviewers.
* Author should mark feedback points (comments) as resolved after requested changes were applied (if feedback required changes).

A merge request requires at least 2 approvals and marked with label `Reviewed`. _Integrator_ may so choose to expedite these rules
under their own discretion (Hotfix, Deployment, etc).

Standards
---

### Coding Standards

* We use [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html)
* Use short array notation
* Use all possible type hinting available in used PHP version
* Business logic does not go in
  * Controllers
  * Commands
  * **Admins**
  * **Doctrine Events**
* Avoid double typed primitive variables. No `null` reference on `string`, `bool`, `array`, `int` etc
* Do not execute native `SQL` queries in an application context. They bypass all OOP related events and result in corrupted data.
* Use proper dependency injection **NO CONTAINER**
* A class should contain not more than 5 dependencies injected.
* Do not leave todo comments when merge request is submitted for code review.
  * Todo comments should exist only during work in progress state and should be fixed by _Author_ as part of the progress.
    * If todo section is unavoidable please create a "maintenance" ticket providing all necessary information with instructions and link

### Test Standards

**Tests are NOT optional**

* Tests follow the [KISS](https://en.wikipedia.org/wiki/KISS_principle) principle
* Add functional tests if you need to test client interaction
* Add integration tests if you need to test a service class
* Add unit tests if you need to test a no-dependency class
* PHPUnit test suite naming conventions MethodName_StateUnderTest_ExpectedBehavior i.e:
  * withdrawMoney_InvalidAccount_ExceptionThrown
  * isAdult_AgeLessThan18_False
  * admitStudent_MissingMandatoryFields_FailToAdmit

### Architectural Standards

* Use [SOLID](https://en.wikipedia.org/wiki/SOLID) principles whenever possible to apply
* Use [design patterns](https://en.wikipedia.org/wiki/Software_design_pattern) whenever possible to apply
  * [Refactoring Guru](https://refactoring.guru/design-patterns)
  * [Design Patterns PHP](https://github.com/DesignPatternsPHP/DesignPatternsPHP)
* Avoid close coupling between classes ([Law of Demeter](https://en.wikipedia.org/wiki/Law_of_Demeter))

### Commit Message Standards

We use the following format for commit messages:

* `SRPDV001-0000 Short description of the changes`

Common comment message format. Use short and concise sentences to describe the changes.

Commit messages should:

* Contain a one-line subject
* Verbs in commit message should be always in **Simple Present tense**
* Contain a reference to a relevant issue number in the body of the commit.
  * This is important for traceability;
* Provide sufficient information for a reviewer to understand the changes
  made and their relationship to previous code.

Commit messages should not:

* Exceed 54 characters in length on the subject line.
* Exceed 72 characters in length in the body of the commit,
  * Except where necessary to maintain the structure of machine-readable or
    machine-generated text (e.g. error messages).

See [Contributing to a Project](http://git-scm.com/book/ch5-2.html) from
Pro Git by Shawn Chacon and Ben Straub for a bit of the rationale behind
these standards.

### Recommendations

* Please commit and push changes created during the day at the end of the working day (even if it is not finished). This will help
  another developer to pick and continue working on the same task. (In case you are out of office next day or other circumstances)

### Performance / Development Goals

We expect all members of team Stripe Connectors to be able to achieve the following performance and development goals in the end of the 2nd month after joining the team!

#### Performance

* All team members communicate clearly and in time with the rest of team
* All team members take active participation in team scrum meetings
* All team members are able te showcase the tickets they work in a sprint
* All team members are active during Grooming & Refinement meetings, and the team can rely on their commitment
* All team members respect and follow the standards of the team's Contribution rules

#### Development

* All team members are able to run the project locally without external help
* All team members are able to debug the code alone
* All team members are able to start and finish a 3 story-point ticket
* All team members are able to assist the team during Quality Assurance and Release
* All team members are caught up with the tech-stack and design patterns of the team
