FROM node:20-alpine

WORKDIR /app

COPY frontend/package*.json ./
RUN npm install

COPY frontend/ .

EXPOSE 4200
CMD ["npm", "start", "--", "--host", "0.0.0.0", "--poll", "2000"]
