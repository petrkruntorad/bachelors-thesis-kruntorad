import os, json, logging
from functions import setCronJob
logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)
#https://stackoverflow.com/questions/44210656/how-to-check-if-a-module-is-installed-in-python-and-if-not-install-it-within-t
try:
    configFile = open('config.json')
    sensors = []
    writeInterval = None

    configData = json.load(configFile)

    if 'writeInterval' in configData.keys():
        writeInterval = configData['writeInterval']
    else:
        raise ValueError("Write interval is missing in config file.")

    if writeInterval:
        setCronJob(writeInterval)

except FileNotFoundError as exception:
    logging.error("Internal error: " + str(exception))
