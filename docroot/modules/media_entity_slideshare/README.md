## Install

Please add this in your composer.json:

```json
{
    "repositories": {
        "ows_slideshare": {
            "type": "vcs",
            "url": "git@github.com:OWS/ZendService_SlideShare.git"
        }
    },
}
```

Because [repositories are only available to the root package and the repositories defined in your dependencies will not be loaded. Read the FAQ entry if you want to learn why.](https://getcomposer.org/doc/05-repositories.md#repository).
