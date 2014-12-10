#!/bin/bash

set -e

# Ensure the remainder of this script is executed in the Scripts directory
CURRENT_DIR="$(pwd)"
SELF_PATH=$(readlink -e "$0" 2>&1) || SELF_PATH=$0
SCRIPT_DIR="$(dirname "${SELF_PATH}")"
if [ "${SCRIPT_DIR}" != "${CURRENT_DIR}" ] ; then
	cd "${SCRIPT_DIR}"
fi

# Only require and use bundler if it is used (i.e. a Gemfile exists)
NEED_BUNDLER=$(test -f Gemfile && echo 1 || echo 0)

# Only require and use bower if it is used (i.e. bower.json exists)
NEED_BOWER=$(test -f bower.json && echo 1 || echo 0)

MISSING_PACKAGES=0

# Find node
NODE="$(which node || true)"
if [ -z "${NODE}" ] ; then
	echo "Grunt runs on NodeJS. Please install NodeJS to your system" >&2
	case "$(uname)" in
		Linux*)
			echo "    On reasonably new linux distributions you can just install 'nodejs' from your package manager." >&2
			echo "    On ubuntu 14.04 you also need to install 'nodejs-legacy' to have 'node' executable" >&2
			echo "    To install these two, run" >&2
			echo "        sudo apt-get install nodejs nodejs-legacy" >&2
			echo "    See also https://github.com/joyent/node/wiki/Installing-Node.js-via-package-manager for further information." >&2
			;;
		Darwin*)
			echo "    One option is to install 'nodejs' via MacPorts or 'node' via Homebrew (includes npm)." >&2
			echo "    See also https://github.com/joyent/node/wiki/Installing-Node.js-via-package-manager for further information." >&2
			;;
		*)
			echo "    Visit the project homepage http://nodejs.org/ and download node.js" >&2
			echo "    Click on 'install' and the right version for your OS will be downloaded and a installer will be started." >&2
			echo "    Use the default configuration." >&2
			;;
	esac
fi

# Find npm
NPM="$(which npm || true)"
if [ -z "${NPM}" ] ; then
	echo "To install all required grunt packages, we need npm." >&2
	case "$(uname)" in
		Linux*)
			echo "    On reasonably new linux distributions you can just install 'npm' from your package manager." >&2
			echo "    On ubuntu 14.04 simply run" >&2
			echo "        sudo apt-get install npm" >&2
			echo "    See also https://github.com/joyent/node/wiki/Installing-Node.js-via-package-manager for further information." >&2
			;;
		Darwin*)
			echo "    One option is to install 'npm' via MacPorts." >&2
			echo "    See also https://github.com/joyent/node/wiki/Installing-Node.js-via-package-manager for further information." >&2
			;;
		*)
			if [ -n "${NODE}" ] ; then
				echo "    Visit the project homepage http://nodejs.org/ and download node.js" >&2
				echo "    Click on 'install' and the right version for your OS will be downloaded and a installer will be started." >&2
				echo "    Use the default configuration." >&2
			fi
			;;
	esac
	MISSING_PACKAGES=1
fi

GRUNT="$(which grunt || true)"
if [ -z "${GRUNT}" ] ; then
	echo "For using grunt we need a global installation of grunt-cli." >&2
	if [ -z "${NPM}" ] ; then
		echo "    To install grunt-cli you have first to install npm, see above." >&2
	fi
	echo "    Install grunt-cli using" >&2
	echo "        npm install -g grunt-cli" >&2
	echo "    Depending on your system you might need administrator privileges for this." >&2
	MISSING_PACKAGES=1
fi

# Find bundler if it is needed
if [ "${NEED_BUNDLER}" -eq 1 ] ; then
	BUNDLE="$(which bundle || true)"
	if [ -z "${BUNDLE}" ] ; then
		echo "For compiling scss into css, we need the correct version of compass installed." >&2
		echo "To ensure that the correct version is used independently of any installed on your system," >&2
		echo "compass is installed locally into the project using bundler." >&2
		echo "To install bundler into your system," >&2
		case "$(uname)" in
			Linux*)
				echo "    use your distributions package manager" >&2
				echo "    On ubuntu simply run" >&2
				echo "        sudo apt-get install bundler" >&2
				;;
#			Darwin*)
#				;;
			*)
				echo "    ensure you have ruby and gem installed, then run the following command:" >&2
				echo "        gem install bundler" >&2
				;;
		esac
		MISSING_PACKAGES=1
	fi
fi

# Find bower if it is needed
if [ "${NEED_BOWER}" -eq 1 ] ; then
	BOWER="$(which bower || true)"
	if [ -z "${BOWER}" ] ; then
		echo "We need bower installed for some tasks." >&2
		if [ -z "${NPM}" ] ; then
			echo "    To install bower you have first to install npm, see above." >&2
		fi
		echo "    Install bower using" >&2
		echo "        npm install -g bower" >&2
		echo "    Depending on your system you might need administrator privileges for this." >&2
		MISSING_PACKAGES=1
	fi
fi

if [ ${MISSING_PACKAGES} -ne 0 ] ; then
	echo >&2
	echo "Missing packages detected. Exiting." >&2
	exit 1
fi

# Install node modules
"${NPM}" install

# Install ruby gems
if [ "${NEED_BUNDLER}" -eq 1 ] ; then
	"${BUNDLE}" install --binstubs --path bundle
fi

# Install bundles
if [ "${NEED_BOWER}" -eq 1 ] ; then
	"${BOWER}" install
fi
