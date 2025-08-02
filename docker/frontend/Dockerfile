FROM node:18-alpine

# Set working directory
WORKDIR /app

# Install dependencies
RUN apk add --no-cache git

# Copy package files
COPY frontend/package*.json ./

# Install npm dependencies
RUN npm ci

# Copy application code
COPY frontend/ .

# Expose port
EXPOSE 3000

# Start development server
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]