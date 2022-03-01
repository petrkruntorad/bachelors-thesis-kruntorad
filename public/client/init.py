import json
import logging
from functions import setCronJob

logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)

try:
    # variable init
    configFile = open('config.json')
    sensors = []
    writeInterval = None
    # loads config
    configData = json.load(configFile)

    # if write interval exist set value to variable
    if 'writeInterval' in configData.keys():
        writeInterval = configData['writeInterval']
    else:
        raise ValueError("Write interval is missing in config file.")

    # if write interval is efined sets cron job
    if writeInterval:
        setCronJob(writeInterval)

except FileNotFoundError as exception:
    logging.error("Internal error: " + str(exception))
