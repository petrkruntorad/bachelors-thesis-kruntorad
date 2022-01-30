import os, time, requests, re

from crontab import CronTab

# variables
sensors = []
temperature = None


def getMainNetworkInterfaceMacAdress():
    cmdipa = os.popen('ip a | grep link/ether').read()
    cmdipa = cmdipa.splitlines()
    allMacAddresses = []
    for mac in cmdipa:
        mac = mac.split(' ')
        allMacAddresses.append(mac[5])

    return allMacAddresses[0]

def touchServer(uniqueHash: str, touchUrl: str):
    macAddress = getMainNetworkInterfaceMacAdress()

    session = requests.Session()

    data = {'uniqueHash': uniqueHash, 'macAddress': macAddress}

    request = session.post(url=touchUrl, data=data)

    return request.text


def loadSensors():
    cmdLs = os.popen('ls /sys/bus/w1/devices/ | grep ^28').read()
    cmdLs = cmdLs.splitlines()
    for id in cmdLs:
        sensors.append(id)
    return sensors


def logEvent(eventContent: str, eventType: str):
    print(eventContent)


def saveTemperatures(sensors: list, writeUrl: str, uniqueHash: str):
    for singleSensor in sensors:
        print(singleSensor)
        with open("/sys/bus/w1/devices/" + singleSensor + "/w1_slave", "r") as file:
            for line in file:
                if "t=" in line:
                    lineSplit = line.split(" ")
                    for value in lineSplit:
                        if value.startswith("t="):
                            temperature = float(value[2:]) / 1000
                            print(temperature)

        if temperature:
            session = requests.Session()

            data = {'sensorId': singleSensor, 'rawSensorData': temperature, 'uniqueHash': uniqueHash}

            insert_request = session.post(url=writeUrl, data=data)

            print(insert_request.text)
        else:
            raise ValueError("Temperature is missing.")

