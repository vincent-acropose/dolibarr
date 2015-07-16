#!/bin/sh
# TODO: check depedencies presence (find, sed and rename)
# TODO: allow execution from build directory
# TODO: validate parameter
# TODO: use multiple word parameter, for example "My module is awesome" which should lead to "MyModuleIsAwesome" and "mymoduleisawesome" so we can also fix language strings
# TODO: add command prototype and help
# TODO: add module ID management (language files…)
# TODO: add oneliner description management
# TODO: add copyright management
ToLower () {
	echo $(echo $1 | tr '[:upper:]' '[:lower:]')
}
ToUpper () {
	echo $(echo $1 | tr '[:lower:]' '[:upper:]')
}


CAMELORIG="MyModule"
LOWERORIG=$(ToLower $CAMELORIG)
UPPERORIG=$(ToUpper $CAMELORIG)
cameltarget=$(echo $1)
lowertarget=$(ToLower $1)
uppertarget=$(ToUpper $1)
thisscript=`basename $0`

# Rewrite occurences
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${CAMELORIG}/${cameltarget}/g"
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${LOWERORIG}/${lowertarget}/g"
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${UPPERORIG}/${uppertarget}/g"

# Rename files
for file in $(find . -not -iwholename '*.git*' -name "*${CAMELORIG}*" -type f)
do
	rename $CAMELORIG $cameltarget $file
done
for file in $(find . -not -iwholename '*.git*' -name "*${LOWERORIG}*" -type f)
do
	rename $LOWERORIG $lowertarget $file
done
for file in $(find . -not -iwholename '*.git*' -name "*${UPPERORIG}*" -type f)
do
	rename $UPPERORIG $uppertarget $file
done

# TODO: add instructions about renaming vars (ack --php -i my)
# TODO: add instructions about renaming files (ls -R|grep -i my)
