<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [2.1.49](https://github.com/liquiddesign/admin/compare/v2.1.48...v2.1.49) (2025-09-04)

### Features

* Extend syncPages callback with containerIndex, enhance opengraph image handling, and add directory creation logic ([064f5f](https://github.com/liquiddesign/admin/commit/064f5fd1fd7058c849150149647cf3add669e534))


---

## [2.1.48](https://github.com/liquiddesign/admin/compare/v2.1.47...v2.1.48) (2025-08-13)

### Features

* Add caching support to AdminFormFactory and AdminForm with page cache cleaning logic ([061dcd](https://github.com/liquiddesign/admin/commit/061dcd9d06a75f4031a9661e35749d444dab8cf1))


---

## [2.1.47](https://github.com/liquiddesign/admin/compare/v2.1.46...v2.1.47) (2025-07-07)

### Bug Fixes

* Adjust shop selection handling and update default value/filter logic in AdminForm ([03f9f6](https://github.com/liquiddesign/admin/commit/03f9f6a4767694b99f8979e8495c2923af0b8b9e))


---

## [2.1.46](https://github.com/liquiddesign/admin/compare/v2.1.45...v2.1.46) (2025-07-07)

### Bug Fixes

* Adjust shop selection handling and update default value/filter logic in AdminForm ([86c811](https://github.com/liquiddesign/admin/commit/86c811e6dc327d8f6a829668c65ea15b042a0d2c))


---

## [2.1.45](https://github.com/liquiddesign/admin/compare/v2.1.44...v2.1.45) (2025-07-02)

### Bug Fixes

* Handle null check for shop primary key in AdminForm dynamic attributes ([2ddc9c](https://github.com/liquiddesign/admin/commit/2ddc9c3b8a2debc0820f19e1a80659554daa784f))


---

## [2.1.44](https://github.com/liquiddesign/admin/compare/v2.1.43...v2.1.44) (2025-06-09)

### Bug Fixes

* Update data-copy-url-targets attribute for dynamic shop URLs in admin form ([bad5bb](https://github.com/liquiddesign/admin/commit/bad5bbde7ef563489a4945d5b1ab5b5c337b421e))
* Update data-copy-url-targets attribute to data-copy in AdminForm ([0847c2](https://github.com/liquiddesign/admin/commit/0847c209235d8a106c369f9f312c26e2e2b514dc))


---

## [2.1.43](https://github.com/liquiddesign/admin/compare/v2.1.42...v2.1.43) (2025-06-09)

### Code Refactoring

* Remove unused shopIcon property from BackendPresenter ([bfc45e](https://github.com/liquiddesign/admin/commit/bfc45e49e27127ffdf42bdd54933c590016926d0))


---

## [2.1.42](https://github.com/liquiddesign/admin/compare/v2.1.41...v2.1.42) (2025-06-04)

### Features

* Add new filter input methods and deprecate old filter input method ([08f3b4](https://github.com/liquiddesign/admin/commit/08f3b487621cf7192bdfa56c0f31950620adbb2b))

### Bug Fixes

* Improve shop selection and account login validation in admin forms ([591645](https://github.com/liquiddesign/admin/commit/591645a01aa7163f0de8d536f4aa0c0673046afc))


---

## [2.1.41](https://github.com/liquiddesign/admin/compare/v2.1.40...v2.1.41) (2025-05-29)

### Bug Fixes

* Correct typo in two-factor authentication email validation message ([e52b0a](https://github.com/liquiddesign/admin/commit/e52b0af2bf8159d848e64e32dc3a024f3d0501b0))


---

## [2.1.40](https://github.com/liquiddesign/admin/compare/v2.1.39...v2.1.40) (2025-05-29)

### Bug Fixes

* Update URL generation to use shop-specific base URLs ([74c1d9](https://github.com/liquiddesign/admin/commit/74c1d96708289118522cd16f1969013b90309e16))


---

## [2.1.39](https://github.com/liquiddesign/admin/compare/v2.1.38...v2.1.39) (2025-05-20)

### Features

* Add addContainer method to manage container instances ([4d7e98](https://github.com/liquiddesign/admin/commit/4d7e98bb5a6fc92727b60dbcbe170f39d9a139cf))


---

## [2.1.38](https://github.com/liquiddesign/admin/compare/v2.1.37...v2.1.38) (2025-05-20)

### Bug Fixes

* Correct return type in addPageContainer method ([d34d07](https://github.com/liquiddesign/admin/commit/d34d07ea895db296192d6049b114ebde5033a018))


---

## [2.1.37](https://github.com/liquiddesign/admin/compare/v2.1.36...v2.1.37) (2025-05-20)

### Chores

* Add jetbrains/phpstorm-attributes to composer.json ([4e22f1](https://github.com/liquiddesign/admin/commit/4e22f1fc5a16e66511ba8481fca0c778d0a08276))
* Update GitHub Actions to use checkout@v4 and cache@v4 ([10dbb5](https://github.com/liquiddesign/admin/commit/10dbb5b2628fa7895df0cdcc46a96c59537dee31))


---

## [2.1.36](https://github.com/liquiddesign/admin/compare/v2.1.35...v2.1.36) (2025-05-20)

### Chores

* Update GitHub Actions to use checkout@v4 and cache@v4 ([4d6871](https://github.com/liquiddesign/admin/commit/4d6871fc333f9ec2cf83444ebbc936e91ce3c0fe))


---

## [2.1.35](https://github.com/liquiddesign/admin/compare/v2.1.34...v2.1.35) (2025-05-13)

### Features


##### Admin Grid

* Add option to toggle paginator visibility ([3af552](https://github.com/liquiddesign/admin/commit/3af5521922d6ae92fcdd6869d3cbb8f51cd8b16a))


---

## [2.1.34](https://github.com/liquiddesign/admin/compare/v2.1.33...v2.1.34) (2025-05-08)

### Features

* Implement AJAX support for form values and add AdminContainer ([d27397](https://github.com/liquiddesign/admin/commit/d2739762bbf56b7943dcb51c7e4838a26e925b0b))


---

## [2.1.33](https://github.com/liquiddesign/admin/compare/v2.1.32...v2.1.33) (2024-11-27)

### Bug Fixes


##### Admin Grid

* Get vars before modifying original collection ([656e9a](https://github.com/liquiddesign/admin/commit/656e9a7e6973155115d3cf2762b9cc3b4fe82b46))


---

## [2.1.32](https://github.com/liquiddesign/admin/compare/v2.1.31...v2.1.32) (2024-10-31)

### Bug Fixes

* Non-existent key if no ajax inputs ([e049e7](https://github.com/liquiddesign/admin/commit/e049e715910b8b1393844d454bf5958ab414e5c2))


---

## [2.1.31](https://github.com/liquiddesign/admin/compare/v2.1.30...v2.1.31) (2024-10-22)

### Features

* Add type template for Administrator in Repository class ([a4e67c](https://github.com/liquiddesign/admin/commit/a4e67c25ebdf6299e5b09122cc17ec295092aeb6))


---

## [2.1.30](https://github.com/liquiddesign/admin/compare/v2.1.29...v2.1.30) (2024-10-21)

### Features

* Add push tags to release script and enhance createButton2 method ([f24f60](https://github.com/liquiddesign/admin/commit/f24f60d02484693d96aed32489adedf1f47c5810))


---

## [2.1.29](https://github.com/liquiddesign/admin/compare/v2.1.28...v2.1.29) (2024-10-21)


---

## [2.1.28](https://github.com/liquiddesign/admin/compare/v2.1.27...v2.1.28) (2024-10-07)

### Features

* Allow null for button link in createButton2 method ([ab4f6d](https://github.com/liquiddesign/admin/commit/ab4f6d42415fd691f4eac53d03489f19531026c4))


---

## [2.1.27](https://github.com/liquiddesign/admin/compare/v2.1.26...v2.1.27) (2024-09-10)

### Features

* Add OpenGraph image upload functionality and adjust image resize ([6ec4b0](https://github.com/liquiddesign/admin/commit/6ec4b0455569392f0af3049f8f88521e0ed56507))


---

## [2.1.26](https://github.com/liquiddesign/admin/compare/v2.1.25...v2.1.26) (2024-09-04)

### Features

* Add persistent paginator toggle and refactor template logic ([bd37eb](https://github.com/liquiddesign/admin/commit/bd37eb87fdf53cebf20155ae085994c2b2ef8e1d))

### Bug Fixes

* Allow nullable shop value in admin form ([f8e820](https://github.com/liquiddesign/admin/commit/f8e8208ad1a79e8dd8bc1867a252dde9d1000964))

### Builds

* Add release-patch script for PowerShell ([02c172](https://github.com/liquiddesign/admin/commit/02c1721817526c207bb09a2327cf7cc8489a67a6))


---

## [2.1.25](https://github.com/liquiddesign/admin/compare/v2.1.24...v2.1.25) (2024-08-11)

### Features

* Add HAVING support to AdminGrid and optimize item count query ([77c88d](https://github.com/liquiddesign/admin/commit/77c88d93e6fa70fc2ecaced1e5c8e3da908eec41))


---

## [2.1.24](https://github.com/liquiddesign/admin/compare/v2.1.23...v2.1.24) (2024-08-01)

### Bug Fixes

* Null entity handling in AdminGrid expression parsing ([245890](https://github.com/liquiddesign/admin/commit/24589062c7759510a68e6f94cf763898b656983a))


---

## [2.1.23](https://github.com/liquiddesign/admin/compare/v2.1.22...v2.1.23) (2024-07-01)

### Features

* Add CSV reader methods to BackendPresenter ([50deaa](https://github.com/liquiddesign/admin/commit/50deaa75c8ff4a1cea7d6c79f8034c73efee9066))


---

## [2.1.22](https://github.com/liquiddesign/admin/compare/v2.1.21...v2.1.22) (2024-06-24)

### Bug Fixes


##### Admin Form Factory

* Associative array ([72f9bd](https://github.com/liquiddesign/admin/commit/72f9bd7d3bc35bceeab9c7fe953415065cecd9b0))


---

## [2.1.21](https://github.com/liquiddesign/admin/compare/v2.1.20...v2.1.21) (2024-06-21)

### Bug Fixes


##### Account Form

* Don't disable shop input ([3768b5](https://github.com/liquiddesign/admin/commit/3768b514b6559aa0d9e4cd313ae7a6c4fe584dbd), [57be69](https://github.com/liquiddesign/admin/commit/57be692d0fb9840472980a3bb366bac07c6e6b3a))


---

## [2.1.20](https://github.com/liquiddesign/admin/compare/v2.1.19...v2.1.20) (2024-06-03)

### Features


##### Backend Presenter

* Add new method createButton2, old methods createButton and createButtonWithClass are deprecated ([dab5db](https://github.com/liquiddesign/admin/commit/dab5db4c569c5fcf62af2be086e25b6bb263801c))


---

## [2.1.19](https://github.com/liquiddesign/admin/compare/v2.1.18...v2.1.19) (2024-05-28)

### Features


##### Admin Form

* Add multiSelectAjax ([055c6e](https://github.com/liquiddesign/admin/commit/055c6ec696dbb35b8cbf6c042dc40948967731b1))

### Styles

* Fix ([2554e7](https://github.com/liquiddesign/admin/commit/2554e7f7af53b63eccb2e84e92bed84edbd9b5d4))


---

## [2.1.18](https://github.com/liquiddesign/admin/compare/v2.1.17...v2.1.18) (2024-05-27)

### Bug Fixes

* Isset ([92cec7](https://github.com/liquiddesign/admin/commit/92cec763fbfa4beb8acb6680fbc8431869a87c14))


---

## [2.1.17](https://github.com/liquiddesign/admin/compare/v2.1.16...v2.1.17) (2024-05-13)

### Bug Fixes

* Nxn bulk edit ([327c52](https://github.com/liquiddesign/admin/commit/327c5278b911820927602ce59eeff20eae901fa8))


---

## [2.1.16](https://github.com/liquiddesign/admin/compare/v2.1.15...v2.1.16) (2024-05-03)

### Features


##### Admin Profile

* Add reset filters button ([573ed7](https://github.com/liquiddesign/admin/commit/573ed716732f79a3a2563d9ff1434798305f9c98))


---

## [2.1.15](https://github.com/liquiddesign/admin/compare/v2.1.14...v2.1.15) (2024-05-03)

### Features


##### Admin Grid

* Reset order in filter form cancel ([0e31bc](https://github.com/liquiddesign/admin/commit/0e31bcb9831a297bbbf840ae65d2e670930d0789))


---

## [2.1.14](https://github.com/liquiddesign/admin/compare/v2.1.13...v2.1.14) (2024-03-25)

### Bug Fixes


##### Bulk Form

* Save correctly collections with shop suffix ([6494f5](https://github.com/liquiddesign/admin/commit/6494f500958c55401d5807c477cef14cc1262cf1))


---

## [2.1.13](https://github.com/liquiddesign/admin/compare/v2.1.12...v2.1.13) (2024-03-21)

### Bug Fixes

* Admin Grid count depends on primary key rather than uuid ([4f142b](https://github.com/liquiddesign/admin/commit/4f142ba6e8ce7518641cd6e21e98801db6d8baa7))


---

## [2.1.12](https://github.com/liquiddesign/admin/compare/v2.1.11...v2.1.12) (2024-03-20)

### Features


##### Administrator

* Add ability to extend form ([8831af](https://github.com/liquiddesign/admin/commit/8831af082b184eb2366162b0621a3901e5b0cdc7))


---

## [2.1.11](https://github.com/liquiddesign/admin/compare/v2.1.10...v2.1.11) (2024-03-08)

### Builds

* PHP 8.1 ([60f852](https://github.com/liquiddesign/admin/commit/60f8521197a786b9b0b7b198ee34cbbde6cf91fb))


---

## [2.1.10](https://github.com/liquiddesign/admin/compare/v2.1.9...v2.1.10) (2024-03-08)

### Builds

* PHP 8.1 ([845832](https://github.com/liquiddesign/admin/commit/845832ca86164929280a4d6c84f7f46b83b2e8b5))


---

## [2.1.9](https://github.com/liquiddesign/admin/compare/v2.1.8...v2.1.9) (2024-03-07)


---

## [2.1.8](https://github.com/liquiddesign/admin/compare/v2.1.7...v2.1.8) (2024-03-07)

### Builds

* Dependency updates ([20ba21](https://github.com/liquiddesign/admin/commit/20ba21d801f0662fec458962cbdbbf31764b8fe7))
* Utils 4.0 dependency ([e0174e](https://github.com/liquiddesign/admin/commit/e0174eadb933c10d53e1afd6ff6551c1fb9c3c52))


---

## [2.1.7](https://github.com/liquiddesign/admin/compare/v2.1.6...v2.1.7) (2024-02-28)

### Features


##### Administrator

* Add ability to extend ([1f5c7a](https://github.com/liquiddesign/admin/commit/1f5c7a2433fc7a4c5971e913a97e06bb996632cc))


---

## [2.1.6](https://github.com/liquiddesign/admin/compare/v2.1.5...v2.1.6) (2024-02-19)

### Performance Improvements

* Get totalNo in BulkForm from paginator ([33f822](https://github.com/liquiddesign/admin/commit/33f822d7834f30738e431c53e6c4c40311dfbb11))

### Builds

* Check commits ([798a85](https://github.com/liquiddesign/admin/commit/798a854b07b7c9ec64002984db8cdd4c5310b026))

### Chores

* Add new changelog system ([03b439](https://github.com/liquiddesign/admin/commit/03b43932c9e571b9558a9b5ec496f73d0dece6d7), [89e039](https://github.com/liquiddesign/admin/commit/89e039cc80ee0c3a58f7073cc3cdb363cc9cb8d6), [b94832](https://github.com/liquiddesign/admin/commit/b948322ae0f9df2075e6a9d26e30ffaddf22a105))
* Archive old changelog ([619117](https://github.com/liquiddesign/admin/commit/6191170277a2424d00ced3de40f3964ea6284a03))


---

