#!/bin/bash
cmd=(dialog --separate-output --checklist "Build Swifty plugin:" 22 76 16)
options=(1 "DIST version" on    # any option can be set to default to "on"
         2 "Send email" off
         3 "SVN update" off
         4 "SVN submit" off)
choices=$("${cmd[@]}" "${options[@]}" 2>&1 >/dev/tty)
clear
startgruntcmd="from_dialog_do"
gruntcmd=$startgruntcmd
for choice in $choices
do
    case $choice in
        1)
            sss="_dist"
            gruntcmd=$gruntcmd$sss
            ;;
        2)
            sss="_mail"
            gruntcmd=$gruntcmd$sss
            ;;
        3)
            sss="_svnupdate"
            gruntcmd=$gruntcmd$sss
            ;;
        4)
            sss="_svnsubmit"
            gruntcmd=$gruntcmd$sss
            ;;
    esac
done
if [ "$gruntcmd" != "$startgruntcmd" ]
then
    echo "#"
    echo "# grunt $gruntcmd"
    echo "#"
    bash -c "grunt $gruntcmd"
fi