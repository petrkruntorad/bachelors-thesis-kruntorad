import json
import logging
import subprocess
import sys
import pkg_resources

from functions import setCronJob

#required = {'crontab'}
#installed = {pkg.key for pkg in pkg_resources.working_set}
#missing = required - installed

#if missing:
#    python = sys.executable
#    subprocess.check_call([python, '-m', 'pip', 'install', *missing], stdout=subprocess.DEVNULL)

logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)
#https://stackoverflow.com/questions/44210656/how-to-check-if-a-module-is-installed-in-python-and-if-not-install-it-within-t
try:
    #variable init
    configFile = open('config.json')
    sensors = []
    writeInterval = None
    #loads config
    configData = json.load(configFile)

    #if write interval exist set value to variable
    if 'writeInterval' in configData.keys():
        writeInterval = configData['writeInterval']
    else:
        raise ValueError("Write interval is missing in config file.")

    #if write interval is efined sets cron job
    if writeInterval:
        setCronJob(writeInterval)

except FileNotFoundError as exception:
    logging.error("Internal error: " + str(exception))
