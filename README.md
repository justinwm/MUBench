<img align="right" width="320" height="320" alt="MUBench Logo" src="https://raw.githubusercontent.com/stg-tud/MUBench/master/meta/logo.png" />

# MUBench : A Benchmark for API-Misuse Detectors

The MUBench dataset is an [MSR 2016 Data Showcase](http://2016.msrconf.org/#/data). Please feel free to [contact Sven Amann](http://www.stg.tu-darmstadt.de/staff/sven_amann), if you have any questions.

## Contributors

* [Sven Amann](http://www.stg.tu-darmstadt.de/staff/sven_amann) (Project Lead)
* [Sarah Nadi](http://www.sarahnadi.org/)
* Hoan A. Nguyen
* [Tien N. Nguyen](http://home.eng.iastate.edu/~tien/)

## Publications

* ['*MUBench: A Benchmark of API-Misuse Detectors*'](http://sven-amann.de/publications/#ANNNM16)

## Run Scripts

1. [Download PyYAML](http://pyyaml.org/wiki/PyYAML) to somewhere on your machine.
2. Unzip the package and install with `python setup.py install`.
3. Run `scripts/verify.py` to check correct setup.
4. Run the script of your choice (see file header for documentation).

## Contribute

To contribute to MUBench, simply use our meta-data template below to describe the API misuse you discovered and [create a new file in the `data` folder](https://github.com/stg-tud/MUBench/new/master/data). You can also create a file locally and submit it via GitHub's drag&drop feature or fork this repository and send a pull request after you committed new misuses.

To make it even easier, you can also simply [fill our online survey](http://goo.gl/forms/3hua7LOFVJ) to submit your finding.

```
source:
  name: Foo
  url:  https://foo.com
project:
  name: A
  url:  http://a.com
report: http://a.com/issues/42
description: >
  Client uses T1.foo() before T2.bar().
crash:    yes|no
internal: yes|no
api:
  - qualified.library.identifier.T1
  - qualified.library.identifier.T2
characteristics:
  - superfluous call
  - missing call
  - wrong call
  - wrong call order
  - missing precondition/predicate
  - missing precondition/null
  - missing precondition/parameter constraint
  - missing catch
  - missing finally
  - ignored result
pattern:
  - single node
  - single object
  - multiple objects
challenges:
  - multi-method
  - multiple usages
  - path dependent
fix:
  description: >
    Fix like so...
  commit: http://a.com/repo/commits/4711
  files:
    - name: src/main/java/a/Client.java
      diff: http://a.com/repo/commits/4711/Client.java
```

## License

[Creative Commons Attribution-ShareAlike 4.0 International (CC BY-SA 4.0)](https://creativecommons.org/licenses/by-sa/4.0/)
