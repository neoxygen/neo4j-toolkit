# Neo4j CLI Toolkit

## Work In Progress

### Manage multiple Neo4j instances from the command line.

![img](http://g.recordit.co/YRVhOJKXdj.gif)


#### Requirements

* PHP5.4+
* Composer

#### Installation

Clone this repository somewhere on your computer :

```bash
git clone git@github.com/neoxygen/neo4j-toolkit
```

Install the dependencies :

```bash
cd neo4j-toolkit
composer install
```

Add the bin folder of the repository to your path (will be soon available as a phar package)

```bash
export PATH=~/Users/you/path/to/neo4j-toolkit/bin:$PATH
```

#### Usage

List the registered databases
```bash
neo db:list
```

Install a new database
```bash
neo db:new testgraph
```

Specify version to install
```bash
neo db:new social 2.1.4
```

Switch from one db to another
```bash
neo db:switch recommendation
```


Todo: Better error handling
Start/stop
....


Author: Christophe Willemsen

