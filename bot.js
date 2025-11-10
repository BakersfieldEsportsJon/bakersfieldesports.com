require('dotenv').config();
const { Client, GatewayIntentBits } = require('discord.js');
const fetch = require('node-fetch');

const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.MessageContent
  ]
});

const EVENT_CHANNEL_ID = '1329851215885897821';
const EVENTS_URL = 'https://bakersfieldesports.com/events/data/events.json';

async function fetchEvents() {
  try {
    const response = await fetch(EVENTS_URL);
    return await response.json();
  } catch (error) {
    console.error('Error fetching events:', error);
    return [];
  }
}

async function createEventAnnouncement(event) {
  const channel = client.channels.cache.get(EVENT_CHANNEL_ID);
  if (!channel) {
    console.error('Event channel not found');
    return;
  }

  const startDate = new Date(event.start);
  const endDate = new Date(startDate.getTime() + 4 * 60 * 60 * 1000);

  const message = `
🎉 **${event.name}** 🎉
📅 ${startDate.toLocaleString()}
📍 ${event.location || 'Online'}
🔗 For more information and to register: https://bakersfieldesports.com/
  `;

  await channel.send(message);
  scheduleReminders(channel, event, startDate);
}

function scheduleReminders(channel, event, startDate) {
  const reminderTimes = [
    { time: getTimeBefore(startDate, 7 * 24 * 60), text: '1 week' },
    { time: getTimeBefore(startDate, 3 * 24 * 60), text: '3 days' },
    { time: getTimeBefore(startDate, 24 * 60), text: '1 day' },
    { time: getTimeBefore(startDate, 1 * 60), text: '1 hour' }
  ];

  reminderTimes.forEach(({ time, text }) => {
    setTimeout(async () => {
      await channel.send(
        `@everyone Reminder: ${event.name} starts in ${text}! 🚀\n` +
        `Register now: https://bakersfieldesports.com/`
      );
    }, time - Date.now());
  });
}

function getTimeBefore(date, minutes) {
  return new Date(date.getTime() - minutes * 60 * 1000);
}

client.once('ready', async () => {
  console.log(`Logged in as ${client.user.tag}`);
  
  const events = await fetchEvents();
  events.forEach(event => {
    createEventAnnouncement(event);
  });
});

client.login(process.env.DISCORD_TOKEN);
