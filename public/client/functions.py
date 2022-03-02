import logging
import os
import requests
import socket
import json
from crontab import CronTab

logging.basicConfig(filename='log.txt', encoding='utf-8', level=logging.ERROR)
# variables
rowComment = 'writeTemperature'
parentDirectory = os.path.dirname(os.path.realpath(__file__))
mainScriptPath = parentDirectory + "/main.py"
cron = CronTab(user='root')


# checks if cron job is already created
def checkIfCronJobExist(comment: str):
    try:
        # iterates cron jobs
        for job in cron:
            # checks if cron job with specified comment exists
            if job.comment == comment:
                return True
        return False
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# creates new cron job
def setCronJob(interval: str):
    try:
        # checks if cronjob exists
        if checkIfCronJobExist(rowComment):
            # updates cronjob
            updateCronJob(interval)
        else:
            # creates new cronjob for main script
            job = cron.new(command='python ' + mainScriptPath, comment=rowComment)
            job.setall(interval)
            cron.write()
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# updates existing cron job
def updateCronJob(interval: str):
    try:
        # looks for cronjob with specified comment
        for job in cron:
            if job.comment == rowComment:
                job.setall(interval)
            cron.write()

    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# loads MAC address for main network interface
def getMainNetworkInterfaceMacAddress():
    try:
        # loads information about network interface
        cmdIpa = os.popen('ip a | grep link/ether').read()
        # splits lines
        cmdIpa = cmdIpa.splitlines()
        allMacAddresses = []
        # iterates through lines and splits them by space
        for mac in cmdIpa:
            mac = mac.split(' ')
            # sets mac address to array
            allMacAddresses.append(mac[5])
        # returns mac address
        return allMacAddresses[0]
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# gets IP address of device
def getIpAddress():
    try:
        # sets socket
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        # connects to 8.8.8.8 to get local IP address
        s.connect(("8.8.8.8", 80))
        # returns local IP address
        return socket.gethostbyname(s.getsockname()[0])
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# updates information about device on server
def touchServer(uniqueHash: str, touchUrl: str):
    try:
        # assigns values to variables
        macAddress = getMainNetworkInterfaceMacAddress()
        ipAddress = getIpAddress()

        session = requests.Session()
        # prepares data
        data = {'uniqueHash': uniqueHash, 'macAddress': macAddress, 'ipAddress': ipAddress}

        # sends data to api
        request = session.post(url=touchUrl, data=data)

        # returns status
        return request.text
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# gets new config from server
def updateConfig(uniqueHash: str, updateUrl: str):
    # gets MAC address
    macAddress = getMainNetworkInterfaceMacAddress()
    # prepares data
    postData = {'uniqueHash': uniqueHash, 'macAddress': macAddress}
    # sends data to api
    response = requests.post(updateUrl, data=postData)
    # checks if status code is 200
    if response.status_code == 200:
        # gets content
        data = response.content
        # prepares path to config
        pathToConfig = parentDirectory + '/config.json'
        # writes content
        with open(pathToConfig, 'wb') as s:
            s.write(data)

        # prepares new config
        configFile = open('config.json')
        # loads config from new file file
        configData = json.load(configFile)

        if 'writeInterval' in configData.keys():
            # updates cron
            updateCronJob(configData['writeInterval'])
        else:
            logging.error("Write interval is missing in config file.")
    else:
        # prints responses
        print(response.content)
        print(response.status_code)


# loads every connected sensor
def loadSensors():
    try:
        sensors = []
        # loads sensors with their names
        cmdLs = os.popen('ls /sys/bus/w1/devices/ | grep ^28').read()
        cmdLs = cmdLs.splitlines()
        for sensorId in cmdLs:
            # adds sensor ids to array
            sensors.append(sensorId)
        return sensors
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))


# saves temperatures to server
def saveTemperatures(sensors: list, writeUrl: str, uniqueHash: str):
    try:
        # gets MAC address
        macAddress = getMainNetworkInterfaceMacAddress()
        # go through every sensor in array
        for singleSensor in sensors:
            temperature = None
            # loads values from sensors
            with open("/sys/bus/w1/devices/" + singleSensor + "/w1_slave", "r") as file:
                for line in file:
                    if "t=" in line:
                        lineSplit = line.split(" ")
                        # iterates through values
                        for value in lineSplit:
                            if value.startswith("t="):
                                # gets correct value by dividing with 1000
                                temperature = float(value[2:]) / 1000
                                print(temperature)
            # if temperature is defined
            if temperature:
                session = requests.Session()
                # prepares data
                data = {'sensorId': singleSensor, 'rawSensorData': temperature, 'uniqueHash': uniqueHash,
                        'macAddress': macAddress}
                # sends data to api
                insert_request = session.post(url=writeUrl, data=data)
                # prints response
                print(insert_request.text)
            else:
                raise ValueError("Temperature is missing.")
    except RuntimeError as exception:
        logging.error("Internal error: " + str(exception))
