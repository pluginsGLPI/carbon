<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [1.2.0](https://github.com/pluginsGLPI/carbon/compare/1.2.0-beta.2...1.2.0) (2026-04-30)

### Features

* Show state of decommission_date in history diagnosis ([e7bede](https://github.com/pluginsGLPI/carbon/commit/e7bede83afd2faa76ca1353a526697f59ad6dcfb))

##### Carbon Emission

* Show total carbon emission of an individual asset ([3426fb](https://github.com/pluginsGLPI/carbon/commit/3426fb3cb6d6fb457ad9b23c4ac6e2c06a09a3fd))

### Bug Fixes


##### Dashboard\ Provider

* Embodied + usage count of impact criteria ([79a5c1](https://github.com/pluginsGLPI/carbon/commit/79a5c13556a22bf1f4e4c331636c556775bf614a))

##### Impact\ Type

* Wrong unit for photochemical ozone formation ([81a9a5](https://github.com/pluginsGLPI/carbon/commit/81a9a54564264dea9200d6432a244e8040c340c6))


---

## [1.2.0-beta.2](https://github.com/pluginsGLPI/carbon/compare/1.2.0-beta.1...1.2.0-beta.2) (2026-04-17)

### Bug Fixes


##### Install

* Dashboard card not properly set ([6e861b](https://github.com/pluginsGLPI/carbon/commit/6e861b158f44642f7a79d7b364f3deca1f96d70a))


---

## [1.2.0-beta.1](https://github.com/pluginsGLPI/carbon/compare/1.1.1...1.2.0-beta.1) (2026-04-10)

### Features

* Asset lifespan for usage impact computation ([c80932](https://github.com/pluginsGLPI/carbon/commit/c8093238acbddf8739bb739556f4ca258cbb2135))
* Better distinctin between legend and completion indicators ([6cdb3d](https://github.com/pluginsGLPI/carbon/commit/6cdb3d81dfb020117671d7f5f6227a3a875786cc), [d67c1c](https://github.com/pluginsGLPI/carbon/commit/d67c1c3ff1a4bf00b17f7d654d06a946fdc06ec1))
* Exclude assets from embodied impact calculation ([b9f00a](https://github.com/pluginsGLPI/carbon/commit/b9f00a233cfc158509f6a7d78806685ac36cc8be))
* Mass deletion of environmental impact results ([3c89de](https://github.com/pluginsGLPI/carbon/commit/3c89de51d500417248c047cdd4e53161daa8cb4e))
* Remove relation from zone to source for carbon inensity ([1791e1](https://github.com/pluginsGLPI/carbon/commit/1791e1167736733d7fea1a75a106e7a673053328))
* Require GLPI timezone enabled ([c0f397](https://github.com/pluginsGLPI/carbon/commit/c0f397a3303d76f3a3936dca21d5ed0403071250))
* Show all individual impacts ([783bed](https://github.com/pluginsGLPI/carbon/commit/783bed5f661fc066547a8be2749e3c376fb928b9))
* Show diagnosis about carbon intensity data sources ([bbde25](https://github.com/pluginsGLPI/carbon/commit/bbde25b29adc3bb3a67b3b066abc3360d85f50bb), [ac9a4c](https://github.com/pluginsGLPI/carbon/commit/ac9a4cd71446f3d19b0d621f5b6b41b5953de986))
* Update and reorganize search options ([9daed0](https://github.com/pluginsGLPI/carbon/commit/9daed09f1bcd802f9f28c4ac0e3276e4d9250068))

##### Abstract Model

* Add new criteria to models ([64b428](https://github.com/pluginsGLPI/carbon/commit/64b428fac93c10aefcdf1c4b1f39c7d506b59fb8))
* Add search options ([1c5dc7](https://github.com/pluginsGLPI/carbon/commit/1c5dc7d4f0fcf970a50a03107c477650e4e23353))
* Add warning about units ([8b4e07](https://github.com/pluginsGLPI/carbon/commit/8b4e07a5b43df83080e9bfae99130ccd3d8a679c))
* Support for more impact criteria ([011d3b](https://github.com/pluginsGLPI/carbon/commit/011d3b8afe1de8e390e5193c40ef8d84ecc63e18))

##### Abstract Type

* Show icon along name in tab ([d1165a](https://github.com/pluginsGLPI/carbon/commit/d1165ac2d59ba05e481fdd672cb5e390a2eb2a01), [ae16c3](https://github.com/pluginsGLPI/carbon/commit/ae16c3d3372d33c1d51fab3aa60637d6101a4dbf))

##### Carbon Intensity

* Report gaps in cron tasks of each client ([074cf4](https://github.com/pluginsGLPI/carbon/commit/074cf45b5627dec967f3695d01838fbfa2649a38))

##### Computer Model, Monitor Model, Network Equipment Model

* User input for enbodied impacts ([662ff6](https://github.com/pluginsGLPI/carbon/commit/662ff658ca2132badb4e59d81963e0a0c62a4266))

##### Computer Model, Monitor Model, Network Equipmentodel

* Explicitly describe the scope of expected values ([ea4e37](https://github.com/pluginsGLPI/carbon/commit/ea4e37b234c0a285761192bbb3ca622b7a7b3610))

##### Computer Type, Monitor Type, Network Equipement Type

* Ignore types of assets ([210841](https://github.com/pluginsGLPI/carbon/commit/2108419f2402c4be2353f08059f2fd9d9d5a3297))

##### Config, Data Source\ Boaviztapi

* Setup url from env var ([5595fe](https://github.com/pluginsGLPI/carbon/commit/5595fe27b9a58166c73540ba093fb43764ca8ff2))

##### Dashboad\ Provider

* Class aliases, emissions per type, add test ([0e9860](https://github.com/pluginsGLPI/carbon/commit/0e98609dc56d178e67f27fcd9dc279e13b616941))

##### Dashboard\ Demo Provider

* Update ([1497d4](https://github.com/pluginsGLPI/carbon/commit/1497d413ef9cb22efa436f2adbdb7c41119072ea))

##### Dashboard\ Grid

* Add all usage impact cards ([677305](https://github.com/pluginsGLPI/carbon/commit/677305aeeb188e6ed46f426e22fa32cd762caaf7))
* Extend usage and total widgets to all criteria ([781545](https://github.com/pluginsGLPI/carbon/commit/781545911b7d9163fe381f329d882690cb9885f1))

##### Data Source/ Abstract Cron Task

* Enforce interface and common code ([c2c71a](https://github.com/pluginsGLPI/carbon/commit/c2c71aa99e59212330508a983de6fdcf272f0958))

##### Data Source\ Carbon Intensity

* Response caching ([9bd951](https://github.com/pluginsGLPI/carbon/commit/9bd951735c9b7a560d07587bcad06ef2afab989f))

##### Embodied Impact

* Add search options ([be0a93](https://github.com/pluginsGLPI/carbon/commit/be0a93aa427b25fb243ca3c7ab5d296c3180003d))

##### Embodied\ Impact

* Add widgets for new impacts ([e4bed7](https://github.com/pluginsGLPI/carbon/commit/e4bed7be7e7929a56c564bc775f3c832251b1a6c))
* Cards for all embodied impacts ([f2d7ce](https://github.com/pluginsGLPI/carbon/commit/f2d7cecc8a81c61cedfc4b1b1e77ef27f56eecec))
* Support for all criterias of Boaviztapi ([a6390a](https://github.com/pluginsGLPI/carbon/commit/a6390a0f826647cd23104b266f207dc06e6c13a7))

##### Impact\ Embodied

* Handle recalculate flag ([3b5ab4](https://github.com/pluginsGLPI/carbon/commit/3b5ab46c1f6f740249aec5b822880a5c70e545f6))

##### Impact\ Usage\ Boavizta

* Support all impact criteria ([864ca8](https://github.com/pluginsGLPI/carbon/commit/864ca8e7e6c86fadbbd342ca728a4e004c697d23))

##### Install

* Add new criterias for usage impact ([fb411e](https://github.com/pluginsGLPI/carbon/commit/fb411ee5bff096df32be9a00abcee92d55b7ae21))
* CLI progress bar when adding fallback carbon intensity ([71c4c2](https://github.com/pluginsGLPI/carbon/commit/71c4c2c36ab4b78abdae0051f4a667576ccf55b9), [419411](https://github.com/pluginsGLPI/carbon/commit/41941184364e40614dce4394c839fe7728740e1a))
* Migrate location / zone relation ([1df029](https://github.com/pluginsGLPI/carbon/commit/1df0297c391382d77352fa7c9734b761ed6fece9))
* Protect against upgrades on too recent database ([bb6b62](https://github.com/pluginsGLPI/carbon/commit/bb6b62372679415db348c57353c0d0fbd32f2ad4))
* Remove upgrade from previous dev versions ([8dc040](https://github.com/pluginsGLPI/carbon/commit/8dc040b1dd7c622949271eb42f35e6cc30b226fb), [3fe870](https://github.com/pluginsGLPI/carbon/commit/3fe87014989d23d333b0b862ebeafd5948fb8026))

##### Location

* Allow selection of sources of fallback level 2 ([e05a5a](https://github.com/pluginsGLPI/carbon/commit/e05a5abaf3ce23e373525ce26e15006eed065108))
* Associate location to a carbon intensity zone ([4c26e4](https://github.com/pluginsGLPI/carbon/commit/4c26e4f02a946682e1cc312ba3c6f3c323c0df20), [ea9388](https://github.com/pluginsGLPI/carbon/commit/ea938815db6e4894ad5cb09202d5d188b1bd630f))
* Massive action, set source_zone ([f1bf2a](https://github.com/pluginsGLPI/carbon/commit/f1bf2a07008880c4974382ca8412cf80c6c27f1e))
* Move plugin specific field for location to a tab ([a09901](https://github.com/pluginsGLPI/carbon/commit/a09901eef440ef996991b6630ad98d67c8313d3a))
* Remove location / carbon intensity relation based on country or state ([018ebd](https://github.com/pluginsGLPI/carbon/commit/018ebd2a6f41cb775cef88712109830fe2b51c4f))
* Update data completion diagnosis ([77e6e3](https://github.com/pluginsGLPI/carbon/commit/77e6e38cbbe8587f0e0ae7039a8102fa91f96cfd))

##### Search Option

* Update search option for historizable status ([f7011c](https://github.com/pluginsGLPI/carbon/commit/f7011ce3efbe8999e25b06981774bac5b1b7c5b2))

##### Source

* Change fallback bool into integer ([a4e7c1](https://github.com/pluginsGLPI/carbon/commit/a4e7c1bb8b11522d06c9035795c156be24ce9ba0))

##### Source Zone

* Better gaps presentation ([3695e4](https://github.com/pluginsGLPI/carbon/commit/3695e499afa9e7f52554908b0bd23bee2c5ff418))

##### Zone

* Update algorithms to find zone from location or asset ([d6f5c2](https://github.com/pluginsGLPI/carbon/commit/d6f5c2abdc5339bc45931e6360847e186f424cd4))

### Bug Fixes

* Add resources files ([180dd7](https://github.com/pluginsGLPI/carbon/commit/180dd76d137d413d6a1df10967a590c6f4fcb6b9), [47ce8a](https://github.com/pluginsGLPI/carbon/commit/47ce8ac5f78e6860d4921fd518e65bd7d6505b94))
* Code lint ([448775](https://github.com/pluginsGLPI/carbon/commit/448775123607609146d8c94ae916004b165f950e))
* CSS not available in expected path ([40ece2](https://github.com/pluginsGLPI/carbon/commit/40ece2074ad7b703eae40344787677af539bcbd4), [f0d36c](https://github.com/pluginsGLPI/carbon/commit/f0d36cc43534cd3ee29383a53eae4cd8dbc48adc))
* Harmonize appearance of reset button with GLPI ([9016fd](https://github.com/pluginsGLPI/carbon/commit/9016fd21d16e1f5aca3a6fe3b5e46c1681d1d20b))
* Images path ([3ebc4d](https://github.com/pluginsGLPI/carbon/commit/3ebc4d4eadf8616eebd6393ae227f4cdcb513bec), [d13d72](https://github.com/pluginsGLPI/carbon/commit/d13d72cc69385a0afb17bf02963668334c3513b5))
* Invalid homepage URL in xml file ([5b501d](https://github.com/pluginsGLPI/carbon/commit/5b501dff2677600c97bb4833c40d9a1066c4b837))
* Prevent recalculate of impacts ([42b04c](https://github.com/pluginsGLPI/carbon/commit/42b04c4beebcaa734f4ac096af20dba88ac214eb))
* Remove duplicated usage impact ([a7535c](https://github.com/pluginsGLPI/carbon/commit/a7535c93ba9aa30e8a2cffdcc94f6401d6e08a0a))
* Templates code style ([9be209](https://github.com/pluginsGLPI/carbon/commit/9be20906bb2e35f623c436e24f18c6e5173a62df), [d43769](https://github.com/pluginsGLPI/carbon/commit/d4376923a20ae5b683b7a319bd7b78a43f5b4fb9), [743dd5](https://github.com/pluginsGLPI/carbon/commit/743dd52c08b8dee2a16cc3f9f1b802447ca4e6df))
* Twig code style ([e2ed46](https://github.com/pluginsGLPI/carbon/commit/e2ed46ad517cc5a1e65b6178bd4bc5f239150744))

##### Abstract Model

* Trim data sources before save ([a89615](https://github.com/pluginsGLPI/carbon/commit/a896158c8daa3e6d0de98dbf5f3ef6a0dd4feecc))

##### Abstract Type, Abstract Model

* Fix type name, for tab display ([9961c9](https://github.com/pluginsGLPI/carbon/commit/9961c92f7f32da5d96def49f32758a2b2368e7d6))

##### Carbon Emission, Computer Type, Monitor Type, Network Equipment Type

* Delete data for purged assets ([9f9245](https://github.com/pluginsGLPI/carbon/commit/9f924567e60db13c67d9105093449e0e816bf67c), [ba96d2](https://github.com/pluginsGLPI/carbon/commit/ba96d2d3561f55ed7638dc7d16c351919cbb00de))

##### Carbon Intensity

* Bad return value" ([00bd1f](https://github.com/pluginsGLPI/carbon/commit/00bd1f86cf078b56a0eb55de17d12dd7def7f18c))
* Html tags visible in unit of values ([b4f5ee](https://github.com/pluginsGLPI/carbon/commit/b4f5eed812e0310bb371cdf488de23c5a394e895))
* Statement exception is not a RuntimeException ([9b9361](https://github.com/pluginsGLPI/carbon/commit/9b9361abf6f6a9098a45c8ddef87f032a1cce220))

##### Carbon Intensity Source Zone

* Method return type ([218965](https://github.com/pluginsGLPI/carbon/commit/218965653b1b2e019fddc80ec1c8e5409d8437f3), [3b8c14](https://github.com/pluginsGLPI/carbon/commit/3b8c14d1597c80d02487a4ac5b59140fe05f5d08))
* Update ajax link for GLPI 11 ([b39d06](https://github.com/pluginsGLPI/carbon/commit/b39d06dac34b7fa722a26048be1e7e4d1fc6e690), [2aefdb](https://github.com/pluginsGLPI/carbon/commit/2aefdb3418b4acde2b03c55cdfe8169a9cd9ebd2))

##### Collect Carbon Intensity Command

* Cannot specify zone if only one available ([c554a2](https://github.com/pluginsGLPI/carbon/commit/c554a264910473682d557d28eca2944ae472e4d7))

##### Command\ Collect Carbon Intensity Command

* Unicity violation error ([382598](https://github.com/pluginsGLPI/carbon/commit/382598d75062872747025ee3872a6bf03ad671de))

##### Computer Type

* Fix deprecated array key null ([a988e5](https://github.com/pluginsGLPI/carbon/commit/a988e5b71b62431753c6a2d0e469a1f8f7a78969))

##### Computer Type, Monitor Type

* More conststent right checks ([925e09](https://github.com/pluginsGLPI/carbon/commit/925e09f89bb2bb8bb90860ccaae3f93e14265a36))

##### Computer Usage Profile

* Cannot edit time start and time stop ([bb1cb9](https://github.com/pluginsGLPI/carbon/commit/bb1cb926cef68a4a2939fd6da5ccfec31c64ac11))
* Deprecated null argument in explode() ([28c075](https://github.com/pluginsGLPI/carbon/commit/28c07567706bc69032836f946cf27edba57160c5), [706999](https://github.com/pluginsGLPI/carbon/commit/706999691dfb00927f35ef99eef30e8b9889c3b4))
* Use native time dropdown field ([68c239](https://github.com/pluginsGLPI/carbon/commit/68c239b81a28e5098aaa3230803754f29a7794f7), [48dced](https://github.com/pluginsGLPI/carbon/commit/48dceda344ea0bfb30ab956a9a37c54242e8dd24))

##### Computer, Monitor, Metwork Equipment

* Properly show status of asset type in diagnosis view ([f0006d](https://github.com/pluginsGLPI/carbon/commit/f0006d2cdb37fc32e6e94196ada4f3df0ea1105e), [08b32a](https://github.com/pluginsGLPI/carbon/commit/08b32ad8cd7a0981aaed8441f3f29095c9f06850))

##### Config

* Geocoding checkbox description ([127f44](https://github.com/pluginsGLPI/carbon/commit/127f445cb765ae51733e2473dbc28d665315f1da))

##### Cron Task

* Typo in description string ([431fbb](https://github.com/pluginsGLPI/carbon/commit/431fbb6dcd53ffacfa9dea24e2ae5600553c6c83))
* Typo in label string ([70390c](https://github.com/pluginsGLPI/carbon/commit/70390c04d25130eb3ab940966f370bc3f9ca292d))

##### Dashboard\ Grid

* Loss of other plugins cards for dashboard ([8954bb](https://github.com/pluginsGLPI/carbon/commit/8954bbe39d9d6cc3eece992bcf78624aa21be877))

##### Dashboard\ Provider

* Arra merge may produce incorrect merged criterias ([dfa0db](https://github.com/pluginsGLPI/carbon/commit/dfa0db51e5a4c625d9d15a25113599e8dd2d5b36), [6dc200](https://github.com/pluginsGLPI/carbon/commit/6dc2000834756721d600f607f330b602b1a5e1fe))

##### Dashboard\ Widget

* Fix bad argument when computing Y scale ([135919](https://github.com/pluginsGLPI/carbon/commit/135919e53d56b3b49732f7e4f8740f25abe8a29e), [f2fa87](https://github.com/pluginsGLPI/carbon/commit/f2fa87272385947e55fa3b1294ff49f6f6f356c2))
* GLPI 11 requires image key specification ([2ae40f](https://github.com/pluginsGLPI/carbon/commit/2ae40f558867c6cd1fb2d2adbcabd0aeeeb8b450), [b7bdee](https://github.com/pluginsGLPI/carbon/commit/b7bdee60e988181e6e54e665dda66634375bb947))

##### Data Source\ Carbon Intensity\ Abstract Source

* Exception handling, null tolerance ([8bc18d](https://github.com/pluginsGLPI/carbon/commit/8bc18dd38e3b64ee6032d5ffccb02fae06653386))

##### Data Source\ Carbon Intensity\ Rte\ Cron Task

* Cron info factorization ([b57275](https://github.com/pluginsGLPI/carbon/commit/b5727530dda10f21ec0882311a753ce938a0dacd))

##### Datasource\ Carbon Intensity\ Electricity Maps

* Factorize and fix date management ([59b5cf](https://github.com/pluginsGLPI/carbon/commit/59b5cf868a8a6de6aa98a24a81a46c4ac2796815))

##### Datasource\ Carbon Intensity\ Electricity Maps\ Client

* Allow paid API key ([7d7ac0](https://github.com/pluginsGLPI/carbon/commit/7d7ac09b7458a4be22ba66b58c885e90f356cce2))

##### Datasource\ Carbon Intensity\RTE

* Improve samples count check and caching file computation ([6adbbf](https://github.com/pluginsGLPI/carbon/commit/6adbbfa709fc0e8b0bbedcec47ad79a5884b2ed8))

##### Docs

* Icon path in metadata ([3d6956](https://github.com/pluginsGLPI/carbon/commit/3d6956e946b439e42f177974da686e165d4bb2ad), [25592c](https://github.com/pluginsGLPI/carbon/commit/25592c1e48590c50b7bd8fc96d671c528a41e789))

##### Embodied Impact

* Bah criteria handling ([217140](https://github.com/pluginsGLPI/carbon/commit/2171404e40cbfa37c0ec0c0f453c8839fba8fd8c))

##### Engine\V1

* Fix fallback selection ([6b98d4](https://github.com/pluginsGLPI/carbon/commit/6b98d4bdcf88246ae05b3277f533baf9e61ee537))

##### Engine\V1\ Abstract Asset

* Fallback carbon intensity may be picked from wrong source ([381800](https://github.com/pluginsGLPI/carbon/commit/38180008f05316db6dfd7b3af2310a4b65a99a5d), [d54028](https://github.com/pluginsGLPI/carbon/commit/d54028c5ed51d8209e2205b87c24f4203c1ffbb3))

##### Impact

* Engine and version of calculations ([ba37e7](https://github.com/pluginsGLPI/carbon/commit/ba37e774d1420ab49fd198b75811a29f40763b33))

##### Impact/ Embodied/ Boavizta/ Computer

* PHP warning, ambiguous instruction ([b818c5](https://github.com/pluginsGLPI/carbon/commit/b818c5ae6101d5f5479a92ef83c669c36bdca7c6))

##### Impact/ Usage/ Abstract Usage Impact

* Useless argument in method call ([085f52](https://github.com/pluginsGLPI/carbon/commit/085f5252e8029a336c4cb2257661092259599835))

##### Impact\ Embodied

* Assets should not be recalculated ([d49b79](https://github.com/pluginsGLPI/carbon/commit/d49b79cb585312d6dfccc25e0c901dcda76cd3aa))

##### Impact\ Embodied\ Abstract Embodied Impact

* Change rule to ignore a value ([e00772](https://github.com/pluginsGLPI/carbon/commit/e00772f8f2bde31d77330c72cab010e4753af6bc))
* Disable check ([18ee3d](https://github.com/pluginsGLPI/carbon/commit/18ee3dcf8c031fa5a5876db1173c06963101663e))
* Typo in error message ([aaf98c](https://github.com/pluginsGLPI/carbon/commit/aaf98c2da31d35a60ba84302537d0afd5b7c1c26))
* Use manual input data if available, instead of Boavizta ([518737](https://github.com/pluginsGLPI/carbon/commit/518737a0d65125e99e12b969cbcdc1df5e314ec0))

##### Impact\ Embodied\ Boavizta

* Improve RAM and HDD description prior query ([9d2496](https://github.com/pluginsGLPI/carbon/commit/9d24960f8309341f10cc0fac2cb7cf43735552b1))
* Service version not saved in DB ([a49430](https://github.com/pluginsGLPI/carbon/commit/a4943099d31246c0e37c31d993e651b26e41d383))

##### Impact\ Embodied\ Boavizta\ Computer

* Ignore removable mass storage [#153](https://github.com/pluginsGLPI/carbon/issues/153) ([c75930](https://github.com/pluginsGLPI/carbon/commit/c75930c8a0d8eeb870b7c6a068860ed9ab992a8a))
* Ignore unidentified manufacturer ([937951](https://github.com/pluginsGLPI/carbon/commit/9379517478f07535f6f6c9f1dbcb36a5c156b4e0))

##### Impact\ Embodied\ Engine, Impact\ Embodied\ Usage

* Bad argument ([0972fd](https://github.com/pluginsGLPI/carbon/commit/0972fd8f8ab3fc77925751699277a3e4b197be36))

##### Impact\ Embodied\ Internal

* Bad object to compute embodied impact from user data ([3a702d](https://github.com/pluginsGLPI/carbon/commit/3a702dc6f63ce10188a0d5e6c03eef6dbc65a749))

##### Impact\ History\ Abstract Asset

* Test memory before calculating a carbon emission ([c57a52](https://github.com/pluginsGLPI/carbon/commit/c57a5258cd794fe672347bc2115cb54497facdc9))

##### Impact\ Type

* Better unit notation ([3c6a12](https://github.com/pluginsGLPI/carbon/commit/3c6a12b3039bfca285ec57d996f0a44b47a224e5))

##### Impact\ Usage\ Computer

* SQL error due to bad relation expression ([e56c02](https://github.com/pluginsGLPI/carbon/commit/e56c0226f012e9248c21c4ef8535dbaf5bad1043), [93164b](https://github.com/pluginsGLPI/carbon/commit/93164b439932a16217c2ce9fe410243771b9d45b))

##### Impat\ Embodied\ Engine

* Do not use internal engine when asset model is empty ([56b9cc](https://github.com/pluginsGLPI/carbon/commit/56b9cccf3b1c86688c9f820258f3450faa94f598))

##### Install

* Avoid use of non-existing classes in migration code ([9c5c94](https://github.com/pluginsGLPI/carbon/commit/9c5c943dd994c6c96d5d06957c3edae5a3757227))
* Bad relation between Quebec and Hydro Quebec ([0ecbf0](https://github.com/pluginsGLPI/carbon/commit/0ecbf0f588f9cf8f4424cf8a38f11fbcd60aa144), [fdee8a](https://github.com/pluginsGLPI/carbon/commit/fdee8aaef063842cbea95eb908f773f9de048f36))
* Follow stricter lint checks ([81c71e](https://github.com/pluginsGLPI/carbon/commit/81c71e9b849f7a6f14607d14150ab1f38be77bcc))
* Make upgrade process repeatable ([dad61e](https://github.com/pluginsGLPI/carbon/commit/dad61eeaea64f714aa09b3e0d91a728b3fadaa32))
* Method always return true ([5e7095](https://github.com/pluginsGLPI/carbon/commit/5e709525bc6505f224fe282db4e057542a0b8cd2))
* More robust inserts in DB ([adc785](https://github.com/pluginsGLPI/carbon/commit/adc7852317a81f1ffbcd2f4eb495e95097917c34), [930107](https://github.com/pluginsGLPI/carbon/commit/930107b65da2523c6ab213d9b1a7e1bdd5836952), [79bbd8](https://github.com/pluginsGLPI/carbon/commit/79bbd8fd35cac200bedaf251dc59fa760746ab25), [50b91b](https://github.com/pluginsGLPI/carbon/commit/50b91bb8aa11d767a3c25d4b1bf4b76e242dfe37))
* Optimize upgrade process and fix not reported error in CLI ([ab4f54](https://github.com/pluginsGLPI/carbon/commit/ab4f5463aafb2743b54e7d83a310c9d23e36dd92))
* Port of fix #76 ([361093](https://github.com/pluginsGLPI/carbon/commit/361093c887814deff88eb7fb22031fa157b33c79), [ece7e2](https://github.com/pluginsGLPI/carbon/commit/ece7e2169a27a65e886e1b88eac098891988fbc8))
* Remove exception handling on install / upgrade ([06a513](https://github.com/pluginsGLPI/carbon/commit/06a513376de5022e4296d90f753f823930030832), [826ee3](https://github.com/pluginsGLPI/carbon/commit/826ee32293fd53a08ed2cf7b41da5f303088d29c))
* Reoder upgrade steps ([8802fe](https://github.com/pluginsGLPI/carbon/commit/8802fe8095ccc9c1a21e532419a532dc08453b4a))
* Show an error on install or upgrade failure ([00c198](https://github.com/pluginsGLPI/carbon/commit/00c198d1573fb077f4cdaf371f1d2cc78ec683ea))

##### Install, Computer Usage Profile

* Upgrade from 1.0.x to 1.1.x must change the time format of usage profiles ([62b5f4](https://github.com/pluginsGLPI/carbon/commit/62b5f4030eb7b1adf69ecf2a36dbce6c15fa2809))

##### Location

* Bad fallback carbon intensity detection ([9b4287](https://github.com/pluginsGLPI/carbon/commit/9b4287936015a172d997db93faf091cdc60a6507))
* Cannot reset a source_zone ([13589a](https://github.com/pluginsGLPI/carbon/commit/13589a54aa5d2582e5492d13e27af5bd9ce0d4b0))
* Check field name in getSpecificValueToSelect ([5a07aa](https://github.com/pluginsGLPI/carbon/commit/5a07aa874372940b5603e9e49f63f54afae0f95f))
* Detect fallback source for carbon intensity ([3deee2](https://github.com/pluginsGLPI/carbon/commit/3deee2b2d5470a1eb4d96a3605404ab5162e9fcc))
* Find source zone when showing Location form ([6ee28e](https://github.com/pluginsGLPI/carbon/commit/6ee28e3cd9f822d8931d8aa499d9b843ad6c9028))
* Reset source_zone again ([48ddb7](https://github.com/pluginsGLPI/carbon/commit/48ddb77494cddb1d506df2ddd03b7191d2c4f776))

##### Search Options

* Simplify SQL, no filter on is_ignored type ([d7077a](https://github.com/pluginsGLPI/carbon/commit/d7077a583838db1c909fc0a6ef11d013ca84d150))

##### Source Zone

* Fail to find a fallback carbon intensity source ([a278d1](https://github.com/pluginsGLPI/carbon/commit/a278d1a5babb4a9281a0a13962114a01eea7065e))
* Typo ([a357bb](https://github.com/pluginsGLPI/carbon/commit/a357bb8c656e04c7d1f26efec140a06ba26a0684))

##### Toolbox

* Filter false gaps ([ff4d22](https://github.com/pluginsGLPI/carbon/commit/ff4d224ccf2a3a69c36168cbc2ad4498efb5d735))
* Remove redundant WHERE criterias ([3a6990](https://github.com/pluginsGLPI/carbon/commit/3a6990f93724be500e0d962a93ac260d39c7eb05))

##### Type

* Search option construction ([d7506e](https://github.com/pluginsGLPI/carbon/commit/d7506e661f986815591d8a9791c1630a63e692e9))

##### Usage Info

* Hide form when nothing to view or edit ([773c72](https://github.com/pluginsGLPI/carbon/commit/773c7245b6a60534177bed4d83355a6878af7491))

##### Widget

* Better choice for energy scale min value ([0b3e86](https://github.com/pluginsGLPI/carbon/commit/0b3e86406672e8276377547e81ce718519951158))

##### Zone

* Bad seach option ([55771d](https://github.com/pluginsGLPI/carbon/commit/55771d16ea1c3c0df730971d1e4c08be6169fdb1))
* Remove obsolete file ([7230a0](https://github.com/pluginsGLPI/carbon/commit/7230a0ff41fdf7a022d89b08d44a2909f3f66b21), [540515](https://github.com/pluginsGLPI/carbon/commit/5405155f5f61afc4326a00749b6d93d783b0ca90))


---

## [1.1.1](https://github.com/pluginsGLPI/carbon/compare/1.1.1...1.1.1) (2026-04-10)

### Features

* Better distinctin between legend and completion indicators ([d67c1c](https://github.com/pluginsGLPI/carbon/commit/d67c1c3ff1a4bf00b17f7d654d06a946fdc06ec1))
* Show diagnosis about carbon intensity data sources ([bbde25](https://github.com/pluginsGLPI/carbon/commit/bbde25b29adc3bb3a67b3b066abc3360d85f50bb))

##### Abstract Type

* Show icon along name in tab ([ae16c3](https://github.com/pluginsGLPI/carbon/commit/ae16c3d3372d33c1d51fab3aa60637d6101a4dbf))

### Bug Fixes

* Invalid homepage URL in xml file ([5b501d](https://github.com/pluginsGLPI/carbon/commit/5b501dff2677600c97bb4833c40d9a1066c4b837))

##### Carbon Intensity Source Zone

* Method return type ([218965](https://github.com/pluginsGLPI/carbon/commit/218965653b1b2e019fddc80ec1c8e5409d8437f3))

##### Computer, Monitor, Metwork Equipment

* Properly show status of asset type in diagnosis view ([f0006d](https://github.com/pluginsGLPI/carbon/commit/f0006d2cdb37fc32e6e94196ada4f3df0ea1105e))

##### Dashboard\ Grid

* Loss of other plugins cards for dashboard ([8954bb](https://github.com/pluginsGLPI/carbon/commit/8954bbe39d9d6cc3eece992bcf78624aa21be877))

##### Dashboard\ Widget

* Fix bad argument when computing Y scale ([f2fa87](https://github.com/pluginsGLPI/carbon/commit/f2fa87272385947e55fa3b1294ff49f6f6f356c2))

##### Engine\V1\ Abstract Asset

* Fallback carbon intensity may be picked from wrong source ([d54028](https://github.com/pluginsGLPI/carbon/commit/d54028c5ed51d8209e2205b87c24f4203c1ffbb3))

##### Impact\ Usage\ Computer

* SQL error due to bad relation expression ([e56c02](https://github.com/pluginsGLPI/carbon/commit/e56c0226f012e9248c21c4ef8535dbaf5bad1043))

##### Install

* Bad relation between Quebec and Hydro Quebec ([fdee8a](https://github.com/pluginsGLPI/carbon/commit/fdee8aaef063842cbea95eb908f773f9de048f36))
* Port of fix #76 ([361093](https://github.com/pluginsGLPI/carbon/commit/361093c887814deff88eb7fb22031fa157b33c79))
* Remove exception handling on install / upgrade ([826ee3](https://github.com/pluginsGLPI/carbon/commit/826ee32293fd53a08ed2cf7b41da5f303088d29c))


---

## [1.1.0](https://github.com/pluginsGLPI/carbon/compare/1.1.1...1.1.0) (2026-04-10)

This version is like 1.1.0 but targets GLPI 11, whereas version 1.0.0 targets GLPI 10.


---

## [1.0.0](https://github.com/pluginsGLPI/carbon/compare/1.1.1...1.0.0) (2026-04-10)

### Features


##### Carbon Intensity

* Add yearly intensity for most countries ([e6b9ef](https://github.com/pluginsGLPI/carbon/commit/e6b9efa528d27aae3d5fa592a3dd4dc023713fc4), [4719e8](https://github.com/pluginsGLPI/carbon/commit/4719e8da8bf5bc832a28dfab5b4721233ddde226))

##### Carbon Intensity Source Zone

* Add tooltip for not downloadable zone ([c06caa](https://github.com/pluginsGLPI/carbon/commit/c06caa1b8827627b473aa0aa80499116c1a5a688))

##### Install

* Remove upgrade from previous dev versions ([77ff1d](https://github.com/pluginsGLPI/carbon/commit/77ff1dd05b4a991463447cb59230fd06b6115516))

### Bug Fixes

* Css file path ([7cbc0b](https://github.com/pluginsGLPI/carbon/commit/7cbc0b0f3aae186c0293ef8342c805bf793f9215))
* Data completion diagnosis inconsistency ([8083fd](https://github.com/pluginsGLPI/carbon/commit/8083fde32b7ea62c50cb636f95e657cda6523210))
* Templates code style ([2961f8](https://github.com/pluginsGLPI/carbon/commit/2961f82d7a6d520853098a9ab18e6c1e48773147))

##### Carbon Emission, Computer Type, Monitor Type, Network Equipment Type

* Delete data for purged assets ([a646a0](https://github.com/pluginsGLPI/carbon/commit/a646a0fcae2bf694cb7c580b2976050b46ea89e7))

##### Carbon Intensity Source Zone

* Do not toggle download for fallback sources ([26e3be](https://github.com/pluginsGLPI/carbon/commit/26e3be8e50ce2bbfa2278c7d6561c70b18efe0f0))
* Server-side check when changing download state of a zone ([7d85ca](https://github.com/pluginsGLPI/carbon/commit/7d85ca026c89d029334af93a3b2eb489ac27a276))

##### Compputer Type

* Prevent update massive action on computer typenative Update massive action cannot perform the change on the field Category. The user must use the specific action 'Update category' ([71679a](https://github.com/pluginsGLPI/carbon/commit/71679a91ef748344218235f56f7e4f3cc2c781e7))

##### Dashboard

* Path to image resource ([debd5e](https://github.com/pluginsGLPI/carbon/commit/debd5e35e4f291fb29f0f9d8f4d408fb10928d9b))

##### Dashboard\ Demo Provider

* Add missing method provider for demo mode ([f4956b](https://github.com/pluginsGLPI/carbon/commit/f4956b5cda5654c7ec67b881d6c6222027b8f4b9))

##### Dashboard\ Provider

* Arra merge may produce incorrect merged criterias ([9eaa39](https://github.com/pluginsGLPI/carbon/commit/9eaa391cd3da9c4df7baf04f175a23b74eb2a655))

##### Data Source\ Carbon Intensity RTE

* Better data source selection ([d365db](https://github.com/pluginsGLPI/carbon/commit/d365db976874734b1781426bcc8ee1efae2f0f81))
* Consolidated data may have step of 15 min ([faaea6](https://github.com/pluginsGLPI/carbon/commit/faaea61018415561af7a8cceb4f58a53942bbac7))
* Use timezone of GLPI ([a39c03](https://github.com/pluginsGLPI/carbon/commit/a39c03c6cea91dc75d96bc65d3ffb87e4828f62c))
* Var not replaced with property ([bd4335](https://github.com/pluginsGLPI/carbon/commit/bd43359828d3777f198742686db7a27dc4965c88))

##### Datasource\ Carbon Intensity RTE

* Fix again winter time switching detection ([1ba6b1](https://github.com/pluginsGLPI/carbon/commit/1ba6b1628f7fee7212c4101edc40d843fd7c9d49))
* Generalize DST switch to winter time ([cd17dc](https://github.com/pluginsGLPI/carbon/commit/cd17dce64b806f687bdde745d40ee5766dd26fbb))

##### Engine\V1\ Abstract Asset

* Search carbon intensity by contry after by state ([4c09c4](https://github.com/pluginsGLPI/carbon/commit/4c09c43cd0aa43a107c803d5232462f538c058e7))

##### Engine\V1\ Abstract Asset, Zone

* Fix fallback carbon intensity ([c81902](https://github.com/pluginsGLPI/carbon/commit/c81902ddb2e65fee8c775e94f4b21978a67b7979))

##### Impact\ Embodied\ Boavizta

* Hardware independant evaluation ([ebe625](https://github.com/pluginsGLPI/carbon/commit/ebe62568c06be3573a20a63b626b0cc031d27a2f))

##### Impact\ History

* Swap 2 historizable status items ([dc7362](https://github.com/pluginsGLPI/carbon/commit/dc7362cf2c7eba6f61d633937d0beea432076aa3))

##### Impact\ History\ Monitor

* Incomplete SQL SELECT statement ([7e4dff](https://github.com/pluginsGLPI/carbon/commit/7e4dff171baa83780e4379a44fe67b5acd3150db))

##### Install

* Add parameters to fgetcsv ([1ce506](https://github.com/pluginsGLPI/carbon/commit/1ce506f58f0a25672311015a582e8731cef69524))
* Link initial sources and zones on fresh install ([1e3800](https://github.com/pluginsGLPI/carbon/commit/1e3800155b62c47a38358c762901083c2b7677d6))
* More robust inserts in DB ([50b91b](https://github.com/pluginsGLPI/carbon/commit/50b91bb8aa11d767a3c25d4b1bf4b76e242dfe37))

##### Report

* Show update right ([3c906c](https://github.com/pluginsGLPI/carbon/commit/3c906c8d8d7ccbfd7e26139038e5a32d76aaff55))

##### Usage Impact

* Allow reset if only gwp was calculated ([4e771c](https://github.com/pluginsGLPI/carbon/commit/4e771ca3c6c7422f1d02ef5b2f37e97084a6d5ee))


---

## [1.0.0-beta.3](https://github.com/pluginsGLPI/carbon/compare/668e7b68956c8fe6decff7563bc64b8057eaa25e...v1.0.0-beta.3) (2025-07-22)

### Features

* Adapt to breaking changes of GLPI 11 ([7a74a6](https://github.com/pluginsGLPI/carbon/commit/7a74a6505081adfc0cd659dadb9abfcd9a0a960f))
* Add search option for historizable status ([ba4fd2](https://github.com/pluginsGLPI/carbon/commit/ba4fd2784a231d1e3cdfffa0d8fc19b364fdb5d6))
* Align dashboard rights to reporting acess right ([2bf0fe](https://github.com/pluginsGLPI/carbon/commit/2bf0feba83fbaa77a081d76fbfc65b6cb1a038c0))
* Allow to recalculate carbon emissiosn for a single asset ([5ac7fb](https://github.com/pluginsGLPI/carbon/commit/5ac7fb19317897211bbf82ebc7f3d63b9e845809))
* Calculate usage impacts, other than warming potential ([d3a20a](https://github.com/pluginsGLPI/carbon/commit/d3a20a3ec1e7fd2b3948490cff4c52f84be05a18))
* Carbon intensity historization, code reorganization for dashboard ([0dc8be](https://github.com/pluginsGLPI/carbon/commit/0dc8beebea7148d92cc647ed6f29d788885e2bf5), [200d78](https://github.com/pluginsGLPI/carbon/commit/200d784ef5761a197e4d89ed93942ac33a06cd2f))
* Check DBMS version ([a384f3](https://github.com/pluginsGLPI/carbon/commit/a384f3b3a6269d57b2dfa5887726b33e19450bfb))
* Compatibility with GLPI 11 ([5399d0](https://github.com/pluginsGLPI/carbon/commit/5399d00c4585c1245ac35a97f20cb629485053e8))
* Computer characterization ([b608a9](https://github.com/pluginsGLPI/carbon/commit/b608a9b05eff96427b1237720bb0330bcbd825bc))
* Historization status legend ([153b18](https://github.com/pluginsGLPI/carbon/commit/153b18be6250391e0f1337dd618e94d8103995b5))
* Keep metadata about source of calculations ([f408e4](https://github.com/pluginsGLPI/carbon/commit/f408e4947da63d4c6c267108de7baee89c60d429))
* Limit biavizta calls to computer only (and refacdtor var names) ([724d83](https://github.com/pluginsGLPI/carbon/commit/724d83aa053a34e951662d7a3ed95704b23b38fb))
* Remove CO2signal API KeY from config ([06326b](https://github.com/pluginsGLPI/carbon/commit/06326bc1b42ce4cfa26f37bba0c758efca4f25fa))
* Search best end of use date of assets ([b6f2b1](https://github.com/pluginsGLPI/carbon/commit/b6f2b1ec23e210e689bb296a091e7b17ce6609c8))
* Show availability of inventory entry date for assets ([41e065](https://github.com/pluginsGLPI/carbon/commit/41e065990c974b6b8da973d6b99a6b6673ed375a))
* Show relation between source and zone ([5f7deb](https://github.com/pluginsGLPI/carbon/commit/5f7debabce4f772ab0144ec97dc0b14641a9bc7b))
* Show usage impact othetr than gwp ([b2ee62](https://github.com/pluginsGLPI/carbon/commit/b2ee62849f5ece262d39a36922ba7aa47e88af4f))
* Use country, state or world carbon intensity ([e59231](https://github.com/pluginsGLPI/carbon/commit/e59231f8eea4ee5fb085573d0eaf3dbefba09eb2))

##### Carbon Emission, Carbon Intensity Source Carbon Intensity Zone

* Update search options ([6e95f4](https://github.com/pluginsGLPI/carbon/commit/6e95f40249592eb186962c67bfddc700438d9604))

##### Carbon Intensity

* Data for world and Quebec ([caa8c9](https://github.com/pluginsGLPI/carbon/commit/caa8c934037fe1ece8be1a518167525c8be58c22))
* Handle date interval filter ([3745f9](https://github.com/pluginsGLPI/carbon/commit/3745f9d32818ad249bd52703c3b82caf8d7d3db0))
* Improve again a message for logs ([7b22af](https://github.com/pluginsGLPI/carbon/commit/7b22af61ca00f2a1bb666eeb2eb70506e3dc2a4a))
* Move carbon intensity data access in dropdowns ([2e8694](https://github.com/pluginsGLPI/carbon/commit/2e86945539c6c1123a398d29498ad4e9e6f86e93))

##### Carbon Intensity Source Carbon Inteisity Zone

* Automatically enable data source downloads ([c612ee](https://github.com/pluginsGLPI/carbon/commit/c612eec6f210a441d531c62e1db1de7fcc2b8fc2))

##### Carbon Intensity Source Carbon Intensity Zone

* Enable / disable zone download ([535586](https://github.com/pluginsGLPI/carbon/commit/5355864157d17ebeddad047e418bd128daa18631))
* Give a link to automatic actions if no zone ([a51578](https://github.com/pluginsGLPI/carbon/commit/a5157851cb1b6a4c56136eebb05752e7f61c8615))
* Show source used for historical ([c5f038](https://github.com/pluginsGLPI/carbon/commit/c5f0383602a9f224fbc034949c5deca49675ac9c))
* Switch downloadable state from zone itemtype ([ee22c3](https://github.com/pluginsGLPI/carbon/commit/ee22c3974dc0f346c211d0faa251dd37381e8623))

##### Carbon Intensity Source Carbon8ntensity Zone

* Guide the user to create zones ([5f9c80](https://github.com/pluginsGLPI/carbon/commit/5f9c80a3ba41e3351cab26c7ed6cbf6b15432620))

##### Carbon Intensity Zone

* Improve search options ([827f8f](https://github.com/pluginsGLPI/carbon/commit/827f8f7af81c4544e4519053e843dabaa9873a1e))
* UI to select a source for historical calculation ([2ac290](https://github.com/pluginsGLPI/carbon/commit/2ac290325de867f062bff3b4f696e6f161b7cfe1))

##### Carbon Intensoty

* More verbose log ([0fcfac](https://github.com/pluginsGLPI/carbon/commit/0fcfac958adcfa2d3587ec7844689d39a36f532e))

##### Cata Source\ Carbon Intensity RTE

* Handle DST ([ce7615](https://github.com/pluginsGLPI/carbon/commit/ce761589bc0740c75a58c963e295c9fbd25af791))

##### Command\ Collect Carbon Intensity Command

* Smarter algoritm handling gaps ([19ddc3](https://github.com/pluginsGLPI/carbon/commit/19ddc3e43b325a159c6ccd1ac86896ed54f8c803))

##### Computer Type

* Mass action to set power consumption ([ce9279](https://github.com/pluginsGLPI/carbon/commit/ce92791ee2726fd9f34306517ea7d271cb26532e))
* Search option and massive action ([31a048](https://github.com/pluginsGLPI/carbon/commit/31a04843e7d93d19e913ddd120e7fc93f3f49a82))
* Use core dropdowns right ([2f9521](https://github.com/pluginsGLPI/carbon/commit/2f952199c84bffc299c7d9ea5ac2013b598652c2), [4028cc](https://github.com/pluginsGLPI/carbon/commit/4028cc1e1a7d56bb8d961643aa6e340fca1a00ef), [62b01e](https://github.com/pluginsGLPI/carbon/commit/62b01ec07ae71697264427490ec9d1eae2957eb9))

##### Computer Usage Profile

* Drop average load field ([973f1c](https://github.com/pluginsGLPI/carbon/commit/973f1c5b6bd977360ed6eceba269d2f0ac24dcd7))
* Make editable ([f96ee5](https://github.com/pluginsGLPI/carbon/commit/f96ee5da0d262113b9b558417dd023bb6cea063f))
* Mass assign an usage profile ([8a6c34](https://github.com/pluginsGLPI/carbon/commit/8a6c34031d439256395bc867bc2d5390bd21c1cc))

##### Computer, Network Equipment, Monitor

* Tooltip on impact values ([82d442](https://github.com/pluginsGLPI/carbon/commit/82d4429aed2a63d64c1e3f9505ae21edb58dc609))

##### Computertype

* New tab to fill power of a computer type ([f906e1](https://github.com/pluginsGLPI/carbon/commit/f906e1f65c59943aaa6b3569f4f20ec30e1e6b0a))
* Replace power class with computertype ([527b56](https://github.com/pluginsGLPI/carbon/commit/527b5652f1f65e78ff17a45780d04b9f7baef36e))

##### Config

* Demo mode ([60c03b](https://github.com/pluginsGLPI/carbon/commit/60c03b8c520c08a623dd0f600c7d9e4800cd5143))
* Selectable embedded impact engine ([3630c7](https://github.com/pluginsGLPI/carbon/commit/3630c71798dd172f4303e539de024e88b452ba8c))

##### Cron Task

* Apply limit to historization task ([0c5371](https://github.com/pluginsGLPI/carbon/commit/0c5371956a6eaf160148902c38a7da7746103596))
* Automatic action to calculate embedded impact ([2c29af](https://github.com/pluginsGLPI/carbon/commit/2c29af038c9576a4692f17d7c126ad35969c5831))

##### Dashboard

* Add new widgets ([14d093](https://github.com/pluginsGLPI/carbon/commit/14d093e5b06063982e70ed4d7d7c9eb45d7e404f))
* Card for multiuple gwp value ([ebe7df](https://github.com/pluginsGLPI/carbon/commit/ebe7df1a3dad76a0f049cd4b2ffca663ede8b1db))
* Embodied global warming potential for reporting page ([e88c47](https://github.com/pluginsGLPI/carbon/commit/e88c47554a38f40e724dbd181ef8009a26f2639a))
* Update default reporting page design ([f820c3](https://github.com/pluginsGLPI/carbon/commit/f820c3924dbd2be6724b2542a3e45eba54869146))
* Update initial dashboard config ([063c11](https://github.com/pluginsGLPI/carbon/commit/063c11c73fb2142fe867f2df4202447df7381ad8))
* Widget counting unhandled computers ([a1c1b6](https://github.com/pluginsGLPI/carbon/commit/a1c1b6861c217522d770ecd53ed91e0c2c93721f))
* Widgets to show handled devices on a single card ([e1b6a0](https://github.com/pluginsGLPI/carbon/commit/e1b6a02cf40a98d5349000a1b277f1816a8745cb))

##### Dashboard Provider

* Count handled computers ([a0beb7](https://github.com/pluginsGLPI/carbon/commit/a0beb7b30b8e622fdb258585cd35203d500235ce))

##### Dashboard/ Dashboard

* Add handleds counter ([881ea7](https://github.com/pluginsGLPI/carbon/commit/881ea744a6a3c8f2c8683274b191e801cd938512))

##### Dashboard\ Povider

* Handled monitors counter ([d3448a](https://github.com/pluginsGLPI/carbon/commit/d3448ae4d1db4d104a919444ca5af48154928468))

##### Dashboard\ Provider

* Add eenergy consumption on carbon emission per month graph ([116c25](https://github.com/pluginsGLPI/carbon/commit/116c25fa445ff1191d25ab67407f89c6a9c42565))
* Click on legend of emissions per model leads to assets list ([d0a02d](https://github.com/pluginsGLPI/carbon/commit/d0a02d9f6b7d4443b302a6195f821cab074b23f6))
* Enbodied impact evauation ([5748e7](https://github.com/pluginsGLPI/carbon/commit/5748e79080dd10cbbd2ba94a7c6298aa1a72dc45))
* Move unit in sub title ([b05a87](https://github.com/pluginsGLPI/carbon/commit/b05a87e0c2fa658a5f873ddf4dd1a1cdfaa7623d))
* Total caarbon emission per type ([31ba70](https://github.com/pluginsGLPI/carbon/commit/31ba70981fcc19f45d3a436a0896b497ef52cb7e), [b43a3a](https://github.com/pluginsGLPI/carbon/commit/b43a3a4b7b92de22de15ff42744aa377c8cb8bf5))

##### Dashboard\ Provider, History\ Computer

* Sum of power per model entity restriction, SQL broken by schema changes ([20b4cd](https://github.com/pluginsGLPI/carbon/commit/20b4cdb262542eb014f7d6d0ab4f18fe48db9cab), [148edd](https://github.com/pluginsGLPI/carbon/commit/148eddf15835e260e326badd65138da8f5f3b2aa))

##### Data Source

* Set data quality when downloading carbon intensities and calculating history ([31767c](https://github.com/pluginsGLPI/carbon/commit/31767c00b4d3e90afb77613f0d2a9dea47096d7f))

##### Data Source\ Abstract Carbon Intensity

* Create sources if not exists in DB, set default source for historical calculation ([5b90e7](https://github.com/pluginsGLPI/carbon/commit/5b90e72f2eb20528323f3e4489473bbb8803e39d))

##### Data Source\ Carbon Intensity RTE

* Alow to download less than 1 day of data ([a4ea11](https://github.com/pluginsGLPI/carbon/commit/a4ea115041e61d3a49369c59d8a23defe52d0ec5))
* Debug incremental download ([2b5ef4](https://github.com/pluginsGLPI/carbon/commit/2b5ef489be2fa3d7939556df408280ef70aa9a88))
* Download history from automatic action ([ef60ea](https://github.com/pluginsGLPI/carbon/commit/ef60ea7213d6965d865503cd179e283caf955543))
* Handle new endpoint for data older than 2023-02 ([130dae](https://github.com/pluginsGLPI/carbon/commit/130dae11134b500cf802f66c31a0d34026c3fb3e))

##### Data Source\ Electricity Map

* Debug ([794a4a](https://github.com/pluginsGLPI/carbon/commit/794a4a7341590040565184db375bcb88b75e14ce))

##### Data Tracking

* Track data quality in the historization process ([3eef68](https://github.com/pluginsGLPI/carbon/commit/3eef68b73c0e987e33a1dac7e8f2ef01e64eb995))

##### Datasource\ Carbon Intensity Interface

* Handle absolute oldest available data ([9222de](https://github.com/pluginsGLPI/carbon/commit/9222deb789f3943cd130ee074c98a2a28f28e372))

##### Datasource\ Carbon Intensity RTE

* More verbosity on error ([ba388e](https://github.com/pluginsGLPI/carbon/commit/ba388ec8566fccf0d5ca81e64345997c90ad2afe))

##### Documentation

* Update URLs for tooltips ([dc6e37](https://github.com/pluginsGLPI/carbon/commit/dc6e37bf7bada423ce51e0ddba92dcc594a3a2f1))

##### Embodied Impact

* Make result more readable ([e40373](https://github.com/pluginsGLPI/carbon/commit/e403730c00666db6f3a9d3d65fa7b70338a60335))

##### Environmental Impact

* UI to reset data for an individual asset ([1dc531](https://github.com/pluginsGLPI/carbon/commit/1dc5310e6b67a15f2f29c6ed47af5d9be69cdf42))

##### Environnemental Impact

* Show emissions for a single computer ([dc8a48](https://github.com/pluginsGLPI/carbon/commit/dc8a489ff50c3a0eafbfddda3a99c9be40beb750))

##### History\ Abstract A Sset

* Diagnosis visual improvement ([696640](https://github.com/pluginsGLPI/carbon/commit/696640893c0f7ee4fa5ff2fb1bb8ccc20c5c5b34))

##### History\ Abstract Asset

* Limit history to last carbon intensity date available ([16dbac](https://github.com/pluginsGLPI/carbon/commit/16dbacb348bfc9e967a1c8d90634cd9049eb6469))
* Use infocom to find start date of historization ([1dc370](https://github.com/pluginsGLPI/carbon/commit/1dc370ddbf7f1f7c26e5fb27b3000010903873fa))

##### History\ Abstract History

* Detect missing rows in historized data ([5aa418](https://github.com/pluginsGLPI/carbon/commit/5aa41830fa95df812c36c2f29a5112ca7270dc9c))

##### History\ Network Equipment

* Add tests ([38cec4](https://github.com/pluginsGLPI/carbon/commit/38cec4e342e0ee08aedac7f176ba610457d5b4ad))

##### Impact\ Embedded\ Biavizta

* Connect to Boaviztapi ([a969af](https://github.com/pluginsGLPI/carbon/commit/a969af810641f0b062833df3a5c92f07c96fa4ee))

##### Impact\ Embodied\ Boavizta

* Merge identical components, increment count instead ([f5e011](https://github.com/pluginsGLPI/carbon/commit/f5e01154d3c613604dbbc25d86a533340e294167))

##### Install

* Declare data sources in DB ([d1c25b](https://github.com/pluginsGLPI/carbon/commit/d1c25b34ebb7fd8fb70647e7863c0590caa2a16e))
* Make install silent if executed in GLPI UI without debug mode ([a58be6](https://github.com/pluginsGLPI/carbon/commit/a58be6382658f9010016e0de6a2b4a1dace0ef4b))
* Migrate search options for core assets ([df17c9](https://github.com/pluginsGLPI/carbon/commit/df17c927e557eb1b91be1fe0b9eabc0396106546))
* Reset dashboard on upgrade ([c341e2](https://github.com/pluginsGLPI/carbon/commit/c341e29506f9b7916baf7b4679ad7c693d70bcea))
* Update display prefs ([b67b5b](https://github.com/pluginsGLPI/carbon/commit/b67b5b5efada79683d058040b56942f74b9887cf))

##### Install, Upgrade

* Upgrade framework ([c72ecc](https://github.com/pluginsGLPI/carbon/commit/c72ecce82c0b04d4e828204dd41a0fb7c57f74cb))

##### Location

* Automatic action for updates ([01caee](https://github.com/pluginsGLPI/carbon/commit/01caeefcc706000da315103a41ea74c38979308e))
* Find Boavizta zone by gocoding ([6ac000](https://github.com/pluginsGLPI/carbon/commit/6ac000a6deda89304c95fc8e4e33a88a88c745da))
* Massive action to set boavizta zone ([2121c7](https://github.com/pluginsGLPI/carbon/commit/2121c7976e45479d50e6ac0312299e32fec9ec9b))

##### Monitor

* New assset handled: monitor ([03ea04](https://github.com/pluginsGLPI/carbon/commit/03ea04afe6f57bc215dbac5be11a9211851df122), [30fede](https://github.com/pluginsGLPI/carbon/commit/30fedeac46ed510527992d188550c7563cef656b))

##### Monitor Type

* Search option for power consumption in asset type ([408d85](https://github.com/pluginsGLPI/carbon/commit/408d850ccb75fc01b0eafc620359095a8c2024c2))

##### Network Equipment

* Handle network equipment ([ce7eb7](https://github.com/pluginsGLPI/carbon/commit/ce7eb74085e701ad5e86d66a48821247ad8742a5))

##### Network Equipment Type, Monitor Type

* Massive action to update power consumption ([bb9a97](https://github.com/pluginsGLPI/carbon/commit/bb9a97976fe293f76c22410b1758257c35633c6d))

##### Power Model Category

* Remove unused file ([6b7584](https://github.com/pluginsGLPI/carbon/commit/6b7584236adaee15ce9781cd89cdb42c112d782e))

##### Power Model, Power Model Category, Power Model Computer Model

* Remove old features ([4161f5](https://github.com/pluginsGLPI/carbon/commit/4161f58f4bb349b1ed8da077517e3a8330c94127))

##### Profile

* Profile rights ([f2d55f](https://github.com/pluginsGLPI/carbon/commit/f2d55fa7bc80a5e0b434021a5fb72d8cd105a2d8))

##### Provider

* Set date labels for empty energy and CO2 emission chart ([1d55f1](https://github.com/pluginsGLPI/carbon/commit/1d55f160e5c80cf62766cc2d70106257d6ff53b9))

##### Report

* Convert report page to a native dashboard ([3f1d37](https://github.com/pluginsGLPI/carbon/commit/3f1d37ba0fdf5cdc6bcea77a07422a914332213c))
* Create report itemtype and menu entry ([812704](https://github.com/pluginsGLPI/carbon/commit/81270494912e323e6b1712d0287f88519b73246b))
* Endpoints to get real data ([a94c47](https://github.com/pluginsGLPI/carbon/commit/a94c47b9b6ffa3927daf5ae38c6540b793208c01))

##### Report, computer Usage Profile

* Reporting, usage profile data ([244501](https://github.com/pluginsGLPI/carbon/commit/2445015afe92689a56993bfdd1dd1124c19abbbc))

##### Usage Info

* Remove obsolete search option ([f7ad96](https://github.com/pluginsGLPI/carbon/commit/f7ad9643516719b4ac97b91d48ec971a545092bc))

##### Usage Profile

* Predefined usage profiles at install ([84e34b](https://github.com/pluginsGLPI/carbon/commit/84e34b877cc95a93fbe329b2e18559507febba58))

##### Usageinfo

* Disable not yet used field ([63b34b](https://github.com/pluginsGLPI/carbon/commit/63b34bd5a56074d94d1d4dfe9c1ce36e17d3c97f))

##### Xomputerpower

* Use model power, then type power, then default power value ([5d046d](https://github.com/pluginsGLPI/carbon/commit/5d046dffd4deba378dca2e4423f17da031948c03))

##### Zone

* Search by state then by location ([51348b](https://github.com/pluginsGLPI/carbon/commit/51348b7b8247b7500d8c1fac286fddc38a36ad4d))

### Bug Fixes

* Bad class name expression for search options aded to assets ([cc7628](https://github.com/pluginsGLPI/carbon/commit/cc762877296aa4f4c83ae7de880794cc4eb3ecf0))
* Check DBMS version, when NOT under test (commented out) ([579299](https://github.com/pluginsGLPI/carbon/commit/579299178cb35c5a2ae0f23e37c2801661fb2461))
* Cleanup unused file ([254e08](https://github.com/pluginsGLPI/carbon/commit/254e08ab7a2a72df9901b2210f20ac6cb27ae336))
* Clear and calculate impacts from an asset page ([78bbd8](https://github.com/pluginsGLPI/carbon/commit/78bbd8cd1914c9be3fbc07d6f25009b0dd21bdf8))
* Computer search option for usage profile ([8c56ff](https://github.com/pluginsGLPI/carbon/commit/8c56ff3e1cf1370c2b31a79b6d7add9fd6402b1b))
* Dashboard appearance when no data available ([9f74a3](https://github.com/pluginsGLPI/carbon/commit/9f74a3f2235dfc097eef7db077df5f89636a41f7))
* Date of carbon intensity for Quebec ([71bd40](https://github.com/pluginsGLPI/carbon/commit/71bd40cfeabb0c21c6136751a1653b4e1df69778))
* Deprecated signature with nullable argument ([24c3ce](https://github.com/pluginsGLPI/carbon/commit/24c3ce4f77e6fbb7a2b7d030b6f01dd13f1b7ab1))
* Disable massive action on power consumption ([7a1fdf](https://github.com/pluginsGLPI/carbon/commit/7a1fdfcaeda3224864bf95a55264638d2ce19cab))
* Drop unused filed ([877a93](https://github.com/pluginsGLPI/carbon/commit/877a9379f909d77ec958688e20e4b931542eb863))
* Fixes deteted by PHPStan ([b203b2](https://github.com/pluginsGLPI/carbon/commit/b203b2483677be6935a0274d785a4ee552359014))
* Historizable diagnosis class / itemtype mapping ([ec0c6f](https://github.com/pluginsGLPI/carbon/commit/ec0c6f510e80df51907d371c585c21899ba5e8ca))
* Intialize only if the plugin is activated ([05bc3a](https://github.com/pluginsGLPI/carbon/commit/05bc3a4cd33981bad411edcd587a11459e86383f))
* Js and css resources hook ([3b8860](https://github.com/pluginsGLPI/carbon/commit/3b8860b8ac06ac42787d641fb80620910f9fdb9b))
* Licence in package.json ([05d89c](https://github.com/pluginsGLPI/carbon/commit/05d89c05121a38a8f895fafb2bc68e543331aeb2))
* Linkfield of search option for asset type's power consumption ([75f738](https://github.com/pluginsGLPI/carbon/commit/75f73897fa65cc1bf6da6c60655efcc51d659860))
* Missing methods ([097f56](https://github.com/pluginsGLPI/carbon/commit/097f564c29edc690872fd140d6315c71bfefca83))
* No need to build css or pics from webpack ([a53808](https://github.com/pluginsGLPI/carbon/commit/a538082449fce9dd1054f3c8e32fe1d63d24f8a7))
* Php warning about non compound use statement ([243142](https://github.com/pluginsGLPI/carbon/commit/243142ace1f7635dc765e11a0ee4fe12f8bdcb52))
* Prefer datetimeinterface for arguments ([da949d](https://github.com/pluginsGLPI/carbon/commit/da949d7a1a8acb5bb08cc8faa8edb0e6807633ca))
* Remove dead code ([7a58bd](https://github.com/pluginsGLPI/carbon/commit/7a58bdd046f3200ec4390581d49768c3b8a2be13))
* Remove useless use statement ([b4a70d](https://github.com/pluginsGLPI/carbon/commit/b4a70d82af3520d16f0d243294ea4c9760b78066))
* Search options applied to Computer and NetworkEquipment ([fd1d4d](https://github.com/pluginsGLPI/carbon/commit/fd1d4deb5cebfdf03e88ec50f637eec149629b0d))
* Typo ([379f80](https://github.com/pluginsGLPI/carbon/commit/379f80c77af88decfb11f3df74261da89635b11a))
* Uniformize asset entry in inventory date ([67538d](https://github.com/pluginsGLPI/carbon/commit/67538d694281fd982ceddba8710ba117b5a8f38a))
* Widgets repair and improvement for native dashboard ([5a1494](https://github.com/pluginsGLPI/carbon/commit/5a1494a4d46363b22087f1c1241124361da2beb8))
* Wrong unit in comment for energy ([adbf74](https://github.com/pluginsGLPI/carbon/commit/adbf742c3fab59aa87598fbada15b73c324ad684))

##### Application\ View\ Extension\ Data Helpers Extension

* Missing class file ([ec30c5](https://github.com/pluginsGLPI/carbon/commit/ec30c5294dc6ae19618a6a0b4d026ddf59d458b6))

##### Carbon Emission

* Check date interval overflow when finding gaps ([0460c7](https://github.com/pluginsGLPI/carbon/commit/0460c782a82bc191166135216bb7d2f76fd6853e))
* Find gap shall use 1 day granularity ([6a7459](https://github.com/pluginsGLPI/carbon/commit/6a7459655cd5568c2ac4963903d6dc74017504f0))
* Incomplete unit in table comment ([47d459](https://github.com/pluginsGLPI/carbon/commit/47d4590059966765a9a46f4301ce992c1e8b9360))
* Loss of precision warning ([108323](https://github.com/pluginsGLPI/carbon/commit/1083239afcc953227ff7be7c0d40634ef348f8b4))
* PrepareInputForAdd ([6bf3f2](https://github.com/pluginsGLPI/carbon/commit/6bf3f2bc41dd42f7236915ef791d295bf437d38d))

##### Carbon Intensity

* Cannot download full history after fresh install ([693b7a](https://github.com/pluginsGLPI/carbon/commit/693b7ac6700c07c4b7c88d3bf2d609c1172de479))
* Intensity is a float ([8348f1](https://github.com/pluginsGLPI/carbon/commit/8348f15df0bd78725d9bbb723041e99a506acd83))
* Prevent fatal error passing null to method ([9ddf9d](https://github.com/pluginsGLPI/carbon/commit/9ddf9d5d15b77dc4c67d921dfd043ebe77e6f7e6))
* Typo ([a76576](https://github.com/pluginsGLPI/carbon/commit/a76576b91c64966c03745656c3979d97652b58b4))

##### Carbon Intensity Source Carbon Intensity Zone

* Make argument optional ([14efc8](https://github.com/pluginsGLPI/carbon/commit/14efc82c5169932cd7949b76f5613165e58c0936))

##### Carbon Intensity Zone

* Bad search option ([07cb79](https://github.com/pluginsGLPI/carbon/commit/07cb797e0e486b0c559a7326804c315a6ee158fc))

##### Computer

* Query fail if no type power consumption row exists ([bac4d4](https://github.com/pluginsGLPI/carbon/commit/bac4d47e534511654e84e42dae738e05f1d8d801))
* SQL compatibility with Mysql 5.7 ([e1848a](https://github.com/pluginsGLPI/carbon/commit/e1848ac91def13b74b0f2a30bb1e44558dbbe255))

##### Computer Type

* Avoid null results in SQL query ([ccc592](https://github.com/pluginsGLPI/carbon/commit/ccc5925e2ab67084283582b836aadfba32f715bf), [e19ab6](https://github.com/pluginsGLPI/carbon/commit/e19ab6ab5883581b215b7f4e5f173873f345c1d3))
* Bad column when querying category ([2f33a0](https://github.com/pluginsGLPI/carbon/commit/2f33a022c3268942043cc307db6751853592f5eb))
* Convert null into 0 ([0ac8d6](https://github.com/pluginsGLPI/carbon/commit/0ac8d6e83884174ba2b412fd0aa07d1a96fc48a6))
* Label should be singular ([774bd1](https://github.com/pluginsGLPI/carbon/commit/774bd1f6624f205cb3c5a6b9e32225897c96e78e))

##### Computer Type, Location

* UI to selct search criteria ([a34be1](https://github.com/pluginsGLPI/carbon/commit/a34be16b8bee4aa7fb864aa530dd3900ff8b844a))

##### Computer Usage Profile

* Bad label ([a3457b](https://github.com/pluginsGLPI/carbon/commit/a3457b4111a51a0e3673a756f0bf3dfb5112f036))
* Cannot assign an usage profile ([c03f85](https://github.com/pluginsGLPI/carbon/commit/c03f8575bffc11793e8bad8ab586d2c1cf28819d))
* Change datatype to get proper search results ([c80d4f](https://github.com/pluginsGLPI/carbon/commit/c80d4f81f7919936a40be10e64222b93add8deb4))
* Data intecrity check, initial items ([b5a803](https://github.com/pluginsGLPI/carbon/commit/b5a803b64640a4b044402bab5058dd13ff519db0))
* Fix translation domain ([f4d958](https://github.com/pluginsGLPI/carbon/commit/f4d9587bea71ca9fa7ef5eadfc8e45b29cf744be))
* Report mass action status ([de8e05](https://github.com/pluginsGLPI/carbon/commit/de8e05243132ce8e520459b7a0f14397508ad797))
* Search options conflict ([c83337](https://github.com/pluginsGLPI/carbon/commit/c8333757211a43ef606385adb383445d10eaf328))

##### Computer Usage Profile, Environnemental Impact

* Add missing files ([afc6ec](https://github.com/pluginsGLPI/carbon/commit/afc6ec8084bc47c6d68308d2e4b3727c14672827))

##### Config

* Acess to config page broken ([62f2f5](https://github.com/pluginsGLPI/carbon/commit/62f2f5d9663f49bdc9782bec504e268bbc0811e4))
* Config page ([ad524a](https://github.com/pluginsGLPI/carbon/commit/ad524acd53afc69888af815ec029a79321d8f844))
* Improve UI for configuration of the plugin ([6cc120](https://github.com/pluginsGLPI/carbon/commit/6cc120513c8e2af6a5acc30b089db24d9d0a74fa))
* Inform about how works the engine selection ([9be15e](https://github.com/pluginsGLPI/carbon/commit/9be15e701af6f14bc9dc9372e5d0d1b02cbff414))
* Initialize config values ([8cf793](https://github.com/pluginsGLPI/carbon/commit/8cf79318198cf82a0ae679b355a43d4ba572b420))
* Protect on screen and DB the API keys ([04331e](https://github.com/pluginsGLPI/carbon/commit/04331edda46aff3c49b83b6aa3825de008fc264a))
* Test Bovizta URL only when changed ([65d943](https://github.com/pluginsGLPI/carbon/commit/65d9431c362515b4fe0eec45e1e4ae71dfabce0d))
* Typo in description text of geocoding ([c4838f](https://github.com/pluginsGLPI/carbon/commit/c4838fe70cb1606d8fdde7983281e66112749990))

##### Create Test Inventory Command

* Add model to computers ([08cf0e](https://github.com/pluginsGLPI/carbon/commit/08cf0e6df81ae7bcb5724ec4338fbe10609cfc27))
* Need GPS coordinates for lcoation ([e96550](https://github.com/pluginsGLPI/carbon/commit/e965506b665380f665f084ce0dc693933acee135))
* Set a creation date for assets ([f1f2b5](https://github.com/pluginsGLPI/carbon/commit/f1f2b5d11ac0a7eec85bf288aed876365a496ef3))

##### Cron Task

* Count of added intensities added not reported ([9d2ea4](https://github.com/pluginsGLPI/carbon/commit/9d2ea4d396f68608c9ad3f0fac9356ab4abcb82e))
* Incomplete rename of automatic action ([aaf72f](https://github.com/pluginsGLPI/carbon/commit/aaf72f479f8eec0037f9a3c1cef2bf26dc180b93))
* Make carbon intensity sownload fault tolerant ([13347d](https://github.com/pluginsGLPI/carbon/commit/13347d1e46c40924403c7708e601136a3c436664))
* May run out of memory in crontasks ([0d63a4](https://github.com/pluginsGLPI/carbon/commit/0d63a433816965c84dc3bb09113f5c83432f8bcb))

##### Dasboard

* Bad URL to header and footer graphics ([684bf8](https://github.com/pluginsGLPI/carbon/commit/684bf89e88425bfeed5da0d09b351bbb36ea3059))

##### Dashboar\ Widget

* Remove unused date interval line from embodied abiotic depletion ([64481d](https://github.com/pluginsGLPI/carbon/commit/64481d6ae7d1007b7e113f63ad22d247ce005789))

##### Dashboard

* Clarify names ([11db0d](https://github.com/pluginsGLPI/carbon/commit/11db0df54ba067e5bd1d0366296044525d3677c9))
* Cleanup dead code ([ff0a51](https://github.com/pluginsGLPI/carbon/commit/ff0a51c625728f0f0e486a39dd9cbf2f88182560))
* Monthly and yearly carbon emission miss 1 day at the end of interval ([21813c](https://github.com/pluginsGLPI/carbon/commit/21813c5238e23e7731cabd9b54968c23a487da0c))
* Not updated class name ([9a9ef7](https://github.com/pluginsGLPI/carbon/commit/9a9ef729710d3782c9adda9dedaf390cdfc05533))
* Typo in widget name ([076915](https://github.com/pluginsGLPI/carbon/commit/0769154673aecdf80e6692a4486d6fe7d07d59f1))
* Unwanted text in template ([06d8ee](https://github.com/pluginsGLPI/carbon/commit/06d8ee0b7520a98b86f48e2077de55f1da36e956))
* Various fixes ([936abf](https://github.com/pluginsGLPI/carbon/commit/936abfd532f3d602fcd29b60f932848e16d74eac))

##### Dashboard\ Demo Provider

* Missing dates in 2 last months co2 emission ([0fc4ab](https://github.com/pluginsGLPI/carbon/commit/0fc4ab1aec98463e216c496be60fb814bfbdfa8b))
* Wrong unit in data ([d5bf56](https://github.com/pluginsGLPI/carbon/commit/d5bf56e61d78c672249c4ff264e7479f055a9f2a))

##### Dashboard\ Provider

* Abiotic depletion potential card title" ([55fe41](https://github.com/pluginsGLPI/carbon/commit/55fe412e9f3394167141b99b1c08a3afb92a792e))
* Computers per model chart ([cbfce9](https://github.com/pluginsGLPI/carbon/commit/cbfce9253ac3ef645e382824839adacfda2ead5d))
* Handle limit ([434749](https://github.com/pluginsGLPI/carbon/commit/434749d7db214fff478b715c4104a7ad2f958096))
* Improve unhandled computers ([febf50](https://github.com/pluginsGLPI/carbon/commit/febf505d93758e4e5b318d31313c0ee950c74e2a))
* Include location in unhandled computers check ([781e77](https://github.com/pluginsGLPI/carbon/commit/781e77fe0f4d1fd9a76a09dab215ef13921bc97d))
* Php warning breaking carbon emissions chart ([f9bb0d](https://github.com/pluginsGLPI/carbon/commit/f9bb0d57690aa0105728767c270bab2e71bd6e02))
* Php warning if chart is not populated ([fd1b42](https://github.com/pluginsGLPI/carbon/commit/fd1b4269445da734bc411686ec56e527e7c1fda7))
* Remove url decode for search URL ([6a5435](https://github.com/pluginsGLPI/carbon/commit/6a5435a41072c5f1fdbd54ae1d68ea4cd00251a9))
* Shorten labels ([e82414](https://github.com/pluginsGLPI/carbon/commit/e824148c86b860b37817358331b90fd493bda556))
* Take into account supported itemtypes only, various fixes ([22ed6d](https://github.com/pluginsGLPI/carbon/commit/22ed6d41acd46d2dafb7e4d59ceb26d683165bd9))
* Uniform unit for series ([2210c8](https://github.com/pluginsGLPI/carbon/commit/2210c87ffe106317614ff3d4d20cf0d392e7ecde))
* Use RTE France to show carbon intensity graph ([aab215](https://github.com/pluginsGLPI/carbon/commit/aab215fb107cdf4eacaa0f282ce98cb7ce7fd431))

##### Dashboard\ Provider, History\ Computer

* Exclude templates and deleted items from handled counts ([e05a2a](https://github.com/pluginsGLPI/carbon/commit/e05a2a10aec20235356b5cab745943960ba1d35b))

##### Dashboard\ Widget

* Disable apex menu, adjust text size ([2d8172](https://github.com/pluginsGLPI/carbon/commit/2d817265b55f343369072642fde758718a8a2882))
* Exception with date format D-M-Y ([0487e1](https://github.com/pluginsGLPI/carbon/commit/0487e1242a850cc58a3330acc49d6b0450a702db))
* Improve title on usage carbon emissions per month ([469d4f](https://github.com/pluginsGLPI/carbon/commit/469d4f75f6e7db953ed77c29fc2d5c9d35c1c3c5))
* Obey to limit of items ([ec1930](https://github.com/pluginsGLPI/carbon/commit/ec19306478467f0dc632afdb5a6b59ad35747f05))
* Reduce default height and move template ([3dc9bb](https://github.com/pluginsGLPI/carbon/commit/3dc9bbcec09b9cf60b8d7bc1023156965a282c91))
* Remove unused template ([70f962](https://github.com/pluginsGLPI/carbon/commit/70f9626a5897ca233a75d4a2543aab2c771f237a))
* Typo (php syntax error) ([0865d1](https://github.com/pluginsGLPI/carbon/commit/0865d1858686410a4cb82d132bd6b5b7683686e8))
* Update embodied primary energy icon ([b6835e](https://github.com/pluginsGLPI/carbon/commit/b6835e234afc26ae4b0a4c16c474c3637c26b78d))
* Use text color for icons ([0182b2](https://github.com/pluginsGLPI/carbon/commit/0182b28478de0abe763360d5a7db7fb19797ed3e))

##### Data Source

* Find zone before create too restrictive ([cf7b81](https://github.com/pluginsGLPI/carbon/commit/cf7b81ba138f7ada2d5b8c8e8df2e6a0ed29d25f))

##### Data Source\ Abstract Carbon Intensity

* Bad interval slicing ([88b058](https://github.com/pluginsGLPI/carbon/commit/88b058bafad23c3c0f157accd047a08d8f94b877))
* Bugs with Daylight saving time (DST) and gap handling ([9e3160](https://github.com/pluginsGLPI/carbon/commit/9e31605e3d0e46c639e172f89c0736bf1c13dd98))
* Bugs with Daylight saving time (DST) and gap handling (continued) ([63946d](https://github.com/pluginsGLPI/carbon/commit/63946df41ec1be9cae3f39b35c08de18091eba82))

##### Data Source\ Boaviztapi

* Better exception handling ([6773b4](https://github.com/pluginsGLPI/carbon/commit/6773b4829ee06b5f55ea7deeee18111fff2eacdc))
* Trigger an exception of URL not configured ([e685b9](https://github.com/pluginsGLPI/carbon/commit/e685b92a710c87210c7d7dc25b3186998a1709b5))

##### Data Source\ Carbon Intensity Electricity Map

* Check the zone exists unreliable ([a8c6ac](https://github.com/pluginsGLPI/carbon/commit/a8c6ac4ed78487ded8acede76f5eeeb6fb1ffb1e))
* Customizable base URL ([098c5c](https://github.com/pluginsGLPI/carbon/commit/098c5c70314c421bba35ede9342efc23a1858afa))

##### Data Source\ Carbon Intensity RTE

* Compatibility with MariaDB ([e24c2f](https://github.com/pluginsGLPI/carbon/commit/e24c2f273c899676eb5a7bdd4f5d781bf94c0cdc))
* Enable historical ([2a08be](https://github.com/pluginsGLPI/carbon/commit/2a08bebaedc14481780db52451b60769d9b1e3c2))
* Increase timeout for incremental download ([abb18b](https://github.com/pluginsGLPI/carbon/commit/abb18b53780b60950393309f98adf819985bf910))

##### Data Source\ Carbon Intensity Rte

* Fix incremental download issues ([fb86d0](https://github.com/pluginsGLPI/carbon/commit/fb86d06cd3509e3e90c807037d4c39521df2f18d))

##### Data Tracking\ Abstract Tracked

* Remove bad type hint ([c5aaf4](https://github.com/pluginsGLPI/carbon/commit/c5aaf4ccd0a81f87d204993682bd069e9f7ca180))

##### Datasource\ Carbon Intensity RTE

* Ensure that we collect full hours in incremental download ([9f8021](https://github.com/pluginsGLPI/carbon/commit/9f802132b5656d8ecdffdaf6973cb780a8c3186d))

##### Embodied Impact

* Not renamed methods and classes ([d0ebc5](https://github.com/pluginsGLPI/carbon/commit/d0ebc537e57735b0d215fb2e1373e93bf95a48f8))

##### Engine\ Abstract Asset

* Improve emissions computation ([83f565](https://github.com/pluginsGLPI/carbon/commit/83f5655580d68ab1b5ef5a2faddf6787a523735a), [79ff09](https://github.com/pluginsGLPI/carbon/commit/79ff0966a205ed74448c259009945ee3c0215fea))

##### Engine\V1\ Abstract Asset

* Select only one source to get carbon intensities ([941210](https://github.com/pluginsGLPI/carbon/commit/9412100c02a7d59f5f2eb9200e60ecc3847267b6))

##### Engine\V1\ Abstract Permanent, Engine\V1\ Abstract Switchable

* Fallback only if no historical data ([dc2fba](https://github.com/pluginsGLPI/carbon/commit/dc2fba01d4df259b87ba020e5f7bf61b7fc7fed0))

##### Engine\V1\ Computer

* Prevent bad argument type ([1930fc](https://github.com/pluginsGLPI/carbon/commit/1930fc90afad18104ffa2f79d44f0c9e4f028047))

##### Engine\V1\ Monitor

* Bad sql query ([216da9](https://github.com/pluginsGLPI/carbon/commit/216da91b2da41b1cd65ecbb570144fa46599388c))

##### History

* Infocom with dates is mandatory ([44541e](https://github.com/pluginsGLPI/carbon/commit/44541e4912f51f9a5ab83c999aa4ba82d62899be))

##### History/ Abstract Asset

* Unexpected null value in method call ([6397ba](https://github.com/pluginsGLPI/carbon/commit/6397ba9a6154f1858c50deb419d04b8f9d5a7def))

##### History\ Abstract Asset

* Disable entity restriction on cron task ([b68e5e](https://github.com/pluginsGLPI/carbon/commit/b68e5ecaff4e8b1bdd5afa6c09be4b7663911cd9))
* Fix getStopDate method ([fef6bd](https://github.com/pluginsGLPI/carbon/commit/fef6bda3028aff3257c3f14561ba56c27fb24cf5))
* Ignore deleted / templates, fix end date boundary ([291f31](https://github.com/pluginsGLPI/carbon/commit/291f3176f788451549b794caad1992ce8e5832d2))

##### History\ Monitor

* Bad SQL query ([5666c6](https://github.com/pluginsGLPI/carbon/commit/5666c62e80e978bca55cfea7d74344446541465e))
* CanHistorize method and tests updates ([dfe4bc](https://github.com/pluginsGLPI/carbon/commit/dfe4bcf4be74edeaa6e4199c07199a57861eacbc))
* Missing entity restriction ([517da9](https://github.com/pluginsGLPI/carbon/commit/517da9f876fb9ea231402b87207a628d6aa1a7c5))
* Twig code not interpreted ([49b000](https://github.com/pluginsGLPI/carbon/commit/49b0002b0dbf4c689bc8967552e3a0752d336040))

##### History\ Monitor, History\ Network Equipment

* Compbatibility with Mysql 5.7 ([2df721](https://github.com/pluginsGLPI/carbon/commit/2df72188e5ed28a742f0511edda2df4cda08f134))

##### History\ Network Equipment

* Bad foreign key ([811c44](https://github.com/pluginsGLPI/carbon/commit/811c4417b918479e55d2b03e536a1ba1b7ffcbd7))
* Historize only asets with enough data ([29d43a](https://github.com/pluginsGLPI/carbon/commit/29d43a59c9b8bf87bc4c1d82fd75f9e4fa944904))
* Remove unused historizable indicator criteria ([c5588d](https://github.com/pluginsGLPI/carbon/commit/c5588d4643581202b0b8c70d0feba9e308af340e))

##### History\ Network Equipment, History\ Monitor

* Fix historizable query ([b64200](https://github.com/pluginsGLPI/carbon/commit/b6420017dde93f8f2603c38a74bbd990e2f86418))

##### Hook

* Mix of old and new stype search option declaration ([1a07a8](https://github.com/pluginsGLPI/carbon/commit/1a07a82f98b23182339cdb020e2f11c56c877426))

##### I18n

* Gettext warning ([d42e7f](https://github.com/pluginsGLPI/carbon/commit/d42e7f9ad7f495ef4dc11c8145a4ba1c4abf1ccd))

##### Impact\ Embodied\ Boavizta

* Improve RAM and HDD description prior query ([6f0e04](https://github.com/pluginsGLPI/carbon/commit/6f0e04e27404b490e0fc801e36e9513b9d807e0c))

##### Impact\ Embodied\ Engine

* Unhandled exception ([d66713](https://github.com/pluginsGLPI/carbon/commit/d66713e5135490d21b5cbd467b2bcff10cc93a28))

##### Impact\ History

* Non GWP usage impact not displayed, management dates not mandatory ([a31fe3](https://github.com/pluginsGLPI/carbon/commit/a31fe3f60d9fc8211d8301cef6ab9f450808a858))

##### Impact\ History\ Abstract Asset

* Deprecated nullable arg in signature ([bd8232](https://github.com/pluginsGLPI/carbon/commit/bd8232532c2aaf277cc72b7732a3fb03ad8c7e6a))
* Timezone loss when converting from datetime to timestamp then back ([99e70d](https://github.com/pluginsGLPI/carbon/commit/99e70d2c94504a542c756c0c2a069423887a6242))

##### Impact\ History\ Computer, Impact\ Histpry\ Network Equipment

* CanHistorize and status inaccurate ([c29fb4](https://github.com/pluginsGLPI/carbon/commit/c29fb4c47c2fb717ea54c6dde7e5bfe3fb891143))

##### Impact\ History\ Monitor

* Use computer's location instead of monitor location ([9448ef](https://github.com/pluginsGLPI/carbon/commit/9448efbb64d1959b02f5250bc8f438dca765d907))

##### Impact\ History\ Network Equipment

* Update historisable SQL query ([38945c](https://github.com/pluginsGLPI/carbon/commit/38945cb140fd7b54602b5ac0c900a6c432be3911))

##### Impact\ Usage\ Boavizta\ Abstract Asset

* Handle unexpected value ([8399a1](https://github.com/pluginsGLPI/carbon/commit/8399a10a7a96c48e029ff3751611072c23127c8c))
* Prevent php warning ([2acfc5](https://github.com/pluginsGLPI/carbon/commit/2acfc5dc24ffd1ce35eb387bfd5116c6feba4333))

##### Install

* Allow forced upgrade from a specific version ([41144f](https://github.com/pluginsGLPI/carbon/commit/41144f98d231eada1d665f08feade332280bf353))
* Bad call when isntalling from UI ([2add98](https://github.com/pluginsGLPI/carbon/commit/2add98b6237ad743353c8d9f52f17972252bafd9))
* Ergument handling fatal error ([0c6051](https://github.com/pluginsGLPI/carbon/commit/0c6051571dca05864e24f052906ad3f579acfb18), [c2432b](https://github.com/pluginsGLPI/carbon/commit/c2432b9b1f4e54402297d445fab70590299edb5e))
* Fix php watnings on upgrade ([500e23](https://github.com/pluginsGLPI/carbon/commit/500e23d5584da3fc100a2fa0b6dc6048050e1af0))
* Move GLPI 11 compatibility changes to upgtrade to 1.0.0 ([efd619](https://github.com/pluginsGLPI/carbon/commit/efd61997260c192c347b6270e9d90b5d7988415e))
* Prevent warnings during installation ([40dbcc](https://github.com/pluginsGLPI/carbon/commit/40dbcc20933cce797645d95f7a60fd3743b1e06c))
* Remove autoincrement values in table creation ([42b89e](https://github.com/pluginsGLPI/carbon/commit/42b89e2d4c71ca0ad52d502330711a2329bceb83))
* Remove the report dashboard on uninstall ([366195](https://github.com/pluginsGLPI/carbon/commit/36619536ee23109569397293d79cf19de1965a27))
* Remove unused configuration value ([c41859](https://github.com/pluginsGLPI/carbon/commit/c418596bd2c16553c22824f9f9e5a6825bef2374))
* Remove unused file ([9f10fa](https://github.com/pluginsGLPI/carbon/commit/9f10fa177cff48053fc95440bea310b4b73ce564))
* Replace datetime with timestamp ([f32d50](https://github.com/pluginsGLPI/carbon/commit/f32d5088f576040eba903513b7c85f560eaa96b6))
* Set db version in config if the last upàgrade step is empty ([3d6bfe](https://github.com/pluginsGLPI/carbon/commit/3d6bfe440a124a20143c25193ff2e2ee41b4e661))
* Table column display length deprecated ([409fe8](https://github.com/pluginsGLPI/carbon/commit/409fe8b71fd26c05c1ad193d915ea8f1e20cf0a7))
* Tables collation and database tests ([d6c9b7](https://github.com/pluginsGLPI/carbon/commit/d6c9b7487f83abaafe3189a83b238b5845227e0a))
* Undefined variable use in automatic action creation failure ([be33ca](https://github.com/pluginsGLPI/carbon/commit/be33ca228965e8728ee8dc329de257cb779a8d90), [609c94](https://github.com/pluginsGLPI/carbon/commit/609c944013673a661c3b0b3ff312f6d84e943054))

##### Install, Uninstall

* Allow forced installation ([ded405](https://github.com/pluginsGLPI/carbon/commit/ded405df1acbef4752c28013ab276e2a3d6f9fd4), [466bc3](https://github.com/pluginsGLPI/carbon/commit/466bc3cb91fe78ecfbd2071fbad22c05ffbabf6a))

##### Lication

* Phpdoc ([aa5ead](https://github.com/pluginsGLPI/carbon/commit/aa5ead1a12405819ebb16b3ee16df2ede5a71b7a))

##### Locales

* Bad locale domain ([062f03](https://github.com/pluginsGLPI/carbon/commit/062f03d39e23660ec4458158d62e9cb546cdc758))

##### Location

* Extra search option applicable to assets only ([5ec4b1](https://github.com/pluginsGLPI/carbon/commit/5ec4b149ef298430919d7365aa6592c63bf574a6))

##### Network Equipment Type

* Bad type name in small title ([cc266e](https://github.com/pluginsGLPI/carbon/commit/cc266ef2a40875516fbc5e872203df40527ba13b))
* Missiing front/ file for update of power consuption ([f2c62d](https://github.com/pluginsGLPI/carbon/commit/f2c62db2c75c470e1af2db3463e87f550513841f))

##### Report

* Css on card class impacts GLPI ([6f6d07](https://github.com/pluginsGLPI/carbon/commit/6f6d07cf9f4267cf6f6d321027dce21904019940))
* Declare itentype has no table ([01dfe3](https://github.com/pluginsGLPI/carbon/commit/01dfe33dc7e59e513ae907921b3f7bde6ee3ee90))
* Dynamically select dates interval and show them in the widgets ([29cf71](https://github.com/pluginsGLPI/carbon/commit/29cf71a9a0b06b4e615d4a23fee5e5c3747fad1d))
* Fix right to update usage profile of computers ([8f5934](https://github.com/pluginsGLPI/carbon/commit/8f5934448160c0b5672e67f2ac9949c5c0b63e85))
* Inverted handled and unhendled counts in template ([e5deba](https://github.com/pluginsGLPI/carbon/commit/e5deba63d360115cb81f907d3496b405cca19911))
* Merge problems ([5fdec6](https://github.com/pluginsGLPI/carbon/commit/5fdec67d92281cb282199d541cb1762f853ad3bc))
* Use foreground color for icons ([19d72f](https://github.com/pluginsGLPI/carbon/commit/19d72f3bb36e38780852b5cc7fcfa493e2e979b1))

##### Report, History\ Abstract Asset

* UI enhancement, handled computers ([ada88a](https://github.com/pluginsGLPI/carbon/commit/ada88aa0723b1a472ed3c6e110f4f9b3f21f9a45))

##### Search Options

* Conflict with Tags plugin ([3d84c8](https://github.com/pluginsGLPI/carbon/commit/3d84c831c960a95d5a6f63b2d2dc6d9c47c147c7))

##### Toolbox

* Avoid localized number formatting ([59ed65](https://github.com/pluginsGLPI/carbon/commit/59ed65243c287a52ce1fea4c1711cb609e5a3b0f))
* Bad column name ([2a0e92](https://github.com/pluginsGLPI/carbon/commit/2a0e925e3466732a0fc456cb0d00c38493367e34))
* Bad operator and logic when scaling a serie of values ([1409ca](https://github.com/pluginsGLPI/carbon/commit/1409ca7cb2098df1ca659ff52c93eb27317330ce))
* Leap years not properly handled to calculate Year to last month ([743b31](https://github.com/pluginsGLPI/carbon/commit/743b319d0c566aa945e13bc90eb009922904f43c))
* No longer use unix timestamps as it causes trouble with non-UTC timezones ([73ba37](https://github.com/pluginsGLPI/carbon/commit/73ba37b9122f95b04d02067e9350ef7c98152695))

##### Tools

* Buils db schema script has bad file names ([080581](https://github.com/pluginsGLPI/carbon/commit/080581291d1fbe82b82082ae76330854ce4d525e), [d24ce7](https://github.com/pluginsGLPI/carbon/commit/d24ce7c844fb26d273350ccff0a68ee92bdfb91c))

##### Uninstall

* Fix possible error when uninstalling the plugin ([ef9b44](https://github.com/pluginsGLPI/carbon/commit/ef9b446ff7d8c0e29c0e98c3ef9b40bdf8567e9b))

##### Zone

* Need entities_id column ([9f7e20](https://github.com/pluginsGLPI/carbon/commit/9f7e2070f4060be7e5c146fc58d5f9c55a8e7e25))


---
