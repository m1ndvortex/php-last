FROM nginx:alpine

# Copy custom nginx config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy site configurations
COPY docker/nginx/sites/ /etc/nginx/sites-available/

# Create sites-enabled directory and link
RUN mkdir -p /etc/nginx/sites-enabled && \
    ln -s /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/

# Create SSL directory
RUN mkdir -p /etc/nginx/ssl

# Expose ports
EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]